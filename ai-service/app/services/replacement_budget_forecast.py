from __future__ import annotations

import logging
from typing import Optional

import numpy as np
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.linear_model import Ridge
from sklearn.metrics import mean_absolute_error
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder

logger = logging.getLogger(__name__)

MIN_TRAINING_RECORDS = 10
MIN_DISTINCT_YEARS = 2


def _clean_training_df(records: list[dict]) -> pd.DataFrame:
    df = pd.DataFrame(records)
    df = df[df["acquisition_value"] > 0].copy()
    df = df.dropna(subset=["category_name", "acquisition_year"])
    df["acquisition_year"] = df["acquisition_year"].astype(int)
    df["acquisition_value"] = df["acquisition_value"].astype(float)
    return df.reset_index(drop=True)


def validate_training_data(records: list[dict]) -> tuple[bool, str, str]:
    """Return (is_valid, error_code, error_message)."""
    if len(records) < MIN_TRAINING_RECORDS:
        return (
            False,
            "INSUFFICIENT_TRAINING_DATA",
            f"ข้อมูลย้อนหลังไม่เพียงพอสำหรับการพยากรณ์ (พบ {len(records)} รายการ ต้องการอย่างน้อย {MIN_TRAINING_RECORDS})",
        )

    df = _clean_training_df(records)

    if len(df) < MIN_TRAINING_RECORDS:
        return (
            False,
            "INSUFFICIENT_TRAINING_DATA",
            f"ข้อมูลย้อนหลังที่ใช้ได้ไม่เพียงพอ (ใช้ได้ {len(df)} รายการ ต้องการอย่างน้อย {MIN_TRAINING_RECORDS})",
        )

    if df["acquisition_year"].nunique() < MIN_DISTINCT_YEARS:
        return (
            False,
            "INSUFFICIENT_YEAR_DIVERSITY",
            "ข้อมูลย้อนหลังต้องมีมากกว่า 1 ปีงบประมาณเพื่อให้โมเดลเรียนรู้แนวโน้ม",
        )

    return True, "", ""


def _build_pipeline() -> Pipeline:
    preprocessor = ColumnTransformer(
        transformers=[
            ("num", "passthrough", ["acquisition_year"]),
            (
                "cat",
                OneHotEncoder(handle_unknown="ignore", sparse_output=False),
                ["category_name"],
            ),
        ]
    )
    return Pipeline(
        steps=[
            ("preprocessor", preprocessor),
            ("regressor", Ridge(alpha=1.0)),
        ]
    )


def _time_split(df: pd.DataFrame) -> tuple[pd.DataFrame, Optional[pd.DataFrame]]:
    max_year = int(df["acquisition_year"].max())
    train_df = df[df["acquisition_year"] < max_year]
    test_df = df[df["acquisition_year"] == max_year]

    # Fall back to full dataset when split leaves too few records
    if len(train_df) < 5 or len(test_df) < 3:
        return df, None

    return train_df, test_df


def train_model(records: list[dict]) -> tuple[Pipeline, Optional[float], Optional[float], int]:
    """Train the Ridge Regression pipeline.

    Returns (pipeline, mae, mape, n_training_records).
    """
    df = _clean_training_df(records)
    train_df, test_df = _time_split(df)

    X_train = train_df[["acquisition_year", "category_name"]]
    y_train = train_df["acquisition_value"].values

    pipeline = _build_pipeline()
    pipeline.fit(X_train, y_train)

    mae: Optional[float] = None
    mape: Optional[float] = None

    if test_df is not None:
        X_test = test_df[["acquisition_year", "category_name"]]
        y_test = test_df["acquisition_value"].values
        y_pred = pipeline.predict(X_test)

        mae = float(mean_absolute_error(y_test, y_pred))

        nonzero = y_test != 0
        if nonzero.sum() > 0:
            mape = float(
                np.mean(np.abs((y_test[nonzero] - y_pred[nonzero]) / y_test[nonzero])) * 100
            )

    return pipeline, mae, mape, len(train_df)


def predict_replacement_costs(
    pipeline: Pipeline,
    candidate_assets: list[dict],
) -> list[dict]:
    """Predict replacement cost for every candidate asset."""
    X = pd.DataFrame(
        [
            {
                "acquisition_year": a["forecast_year"],
                "category_name": a["category_name"],
            }
            for a in candidate_assets
        ]
    )

    raw_preds = pipeline.predict(X)

    results: list[dict] = []
    for asset, cost in zip(candidate_assets, raw_preds):
        predicted = max(0.0, float(cost))
        results.append({**asset, "predicted_replacement_cost": round(predicted, 2)})

    return results


def run_forecast(
    forecast_years: int,
    training_records: list[dict],
    candidate_assets: list[dict],
) -> dict:
    """Main entry point called by the FastAPI endpoint."""
    # Validate training data
    valid, error_code, error_message = validate_training_data(training_records)
    if not valid:
        return {"success": False, "code": error_code, "message": error_message}

    # Train
    try:
        pipeline, mae, mape, n_train = train_model(training_records)
    except Exception as exc:
        logger.error("Model training failed: %s", exc, exc_info=True)
        return {"success": False, "code": "MODEL_ERROR", "message": "เกิดข้อผิดพลาดระหว่างการเรียนรู้โมเดล"}

    # Predict
    try:
        predicted_assets = predict_replacement_costs(pipeline, candidate_assets)
    except Exception as exc:
        logger.error("Prediction failed: %s", exc, exc_info=True)
        return {"success": False, "code": "MODEL_ERROR", "message": "เกิดข้อผิดพลาดระหว่างการพยากรณ์"}

    # Aggregate by year
    years_dict: dict[int, dict] = {}
    for asset in predicted_assets:
        yr = asset["forecast_year"]
        if yr not in years_dict:
            years_dict[yr] = {"count": 0, "budget": 0.0}
        years_dict[yr]["count"] += 1
        years_dict[yr]["budget"] += asset["predicted_replacement_cost"]

    years_list = [
        {
            "year": yr,
            "asset_count": data["count"],
            "forecast_budget": round(data["budget"], 2),
        }
        for yr, data in sorted(years_dict.items())
    ]

    total_budget = sum(a["predicted_replacement_cost"] for a in predicted_assets)

    return {
        "success": True,
        "forecast_years": forecast_years,
        "total_assets": len(predicted_assets),
        "total_forecast_budget": round(total_budget, 2),
        "years": years_list,
        "assets": [
            {
                "asset_code": a.get("asset_code", "-"),
                "asset_name": a.get("asset_name"),
                "category_name": a.get("category_name", "-"),
                "organization_name": a.get("organization_name"),
                "acceptance_date": a.get("acceptance_date"),
                "forecast_year": a.get("forecast_year"),
                "current_value": a.get("current_value"),
                "predicted_replacement_cost": a.get("predicted_replacement_cost", 0.0),
            }
            for a in predicted_assets
        ],
        "model": {
            "name": "Ridge Regression",
            "training_records": n_train,
            "mae": round(mae, 2) if mae is not None else None,
            "mape": round(mape, 2) if mape is not None else None,
        },
    }
