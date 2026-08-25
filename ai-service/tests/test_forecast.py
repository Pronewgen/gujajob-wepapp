from __future__ import annotations

import pytest
from fastapi.testclient import TestClient

from app.main import app
from app.services.replacement_budget_forecast import (
    run_forecast,
    validate_training_data,
)

client = TestClient(app)


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def _make_training_records(n: int = 30, start_year: int = 2015) -> list[dict]:
    """Generate synthetic training records spanning several years and categories."""
    categories = [
        "เครื่องคอมพิวเตอร์",
        "เครื่องพิมพ์",
        "โต๊ะทำงาน",
        "เก้าอี้",
        "เครื่องปรับอากาศ",
    ]
    prices = [25000, 8000, 5000, 3000, 35000]
    records = []
    for i in range(n):
        cat_idx = i % len(categories)
        year = start_year + (i % 8)
        records.append(
            {
                "acquisition_year": year,
                "category_id": cat_idx + 1,
                "category_name": categories[cat_idx],
                "acquisition_value": float(prices[cat_idx]) + (i * 500),
            }
        )
    return records


def _make_candidate_assets(forecast_year: int = 2027, n: int = 3) -> list[dict]:
    categories = ["เครื่องคอมพิวเตอร์", "เครื่องพิมพ์", "เครื่องปรับอากาศ"]
    return [
        {
            "asset_id": i + 1,
            "asset_code": f"ASS-{i + 1:04d}",
            "asset_name": f"ครุภัณฑ์ {i + 1}",
            "category_id": i + 1,
            "category_name": categories[i % len(categories)],
            "organization_id": 1,
            "organization_name": "สำนักงานกลาง",
            "acceptance_date": "01/01/2020",
            "current_value": 5000.0,
            "forecast_year": forecast_year,
        }
        for i in range(n)
    ]


# ---------------------------------------------------------------------------
# 1. Health endpoint
# ---------------------------------------------------------------------------

def test_health():
    response = client.get("/health")
    assert response.status_code == 200
    assert response.json() == {"status": "ok"}


# ---------------------------------------------------------------------------
# 2. Valid forecast_years (1, 2, 3)
# ---------------------------------------------------------------------------

@pytest.mark.parametrize("years", [1, 2, 3])
def test_valid_forecast_years(years: int):
    payload = {
        "forecast_years": years,
        "training_records": _make_training_records(30),
        "candidate_assets": _make_candidate_assets(forecast_year=2027, n=2),
    }
    response = client.post("/forecast/replacement-budget", json=payload)
    assert response.status_code == 200
    data = response.json()
    assert data["success"] is True
    assert data["forecast_years"] == years


# ---------------------------------------------------------------------------
# 3. Reject invalid forecast_years (0 and 4)
# ---------------------------------------------------------------------------

@pytest.mark.parametrize("invalid_years", [0, 4, -1, 10])
def test_invalid_forecast_years(invalid_years: int):
    payload = {
        "forecast_years": invalid_years,
        "training_records": _make_training_records(30),
        "candidate_assets": _make_candidate_assets(),
    }
    response = client.post("/forecast/replacement-budget", json=payload)
    assert response.status_code == 422  # Pydantic validation error


# ---------------------------------------------------------------------------
# 4. Insufficient training data
# ---------------------------------------------------------------------------

def test_insufficient_training_data():
    payload = {
        "forecast_years": 1,
        "training_records": _make_training_records(n=3),  # below MIN_TRAINING_RECORDS
        "candidate_assets": _make_candidate_assets(),
    }
    response = client.post("/forecast/replacement-budget", json=payload)
    assert response.status_code == 422
    data = response.json()
    assert data["success"] is False
    assert data["code"] == "INSUFFICIENT_TRAINING_DATA"


# ---------------------------------------------------------------------------
# 5. Empty candidate assets — returns success with zero budget
# ---------------------------------------------------------------------------

def test_empty_candidate_assets():
    payload = {
        "forecast_years": 1,
        "training_records": _make_training_records(30),
        "candidate_assets": [],
    }
    response = client.post("/forecast/replacement-budget", json=payload)
    assert response.status_code == 200
    data = response.json()
    assert data["success"] is True
    assert data["total_assets"] == 0
    assert data["total_forecast_budget"] == 0.0
    assert data["years"] == []


# ---------------------------------------------------------------------------
# 6. Successful prediction with sufficient data
# ---------------------------------------------------------------------------

def test_successful_prediction():
    candidates = _make_candidate_assets(forecast_year=2027, n=5)
    payload = {
        "forecast_years": 2,
        "training_records": _make_training_records(40),
        "candidate_assets": candidates,
    }
    response = client.post("/forecast/replacement-budget", json=payload)
    assert response.status_code == 200
    data = response.json()
    assert data["success"] is True
    assert data["total_assets"] == len(candidates)
    assert len(data["assets"]) == len(candidates)
    for asset in data["assets"]:
        assert asset["predicted_replacement_cost"] >= 0


# ---------------------------------------------------------------------------
# 7. Output total equals sum of per-year budgets
# ---------------------------------------------------------------------------

def test_total_equals_sum_of_years():
    candidates = (
        _make_candidate_assets(forecast_year=2027, n=3)
        + _make_candidate_assets(forecast_year=2028, n=2)
    )
    payload = {
        "forecast_years": 2,
        "training_records": _make_training_records(40),
        "candidate_assets": candidates,
    }
    response = client.post("/forecast/replacement-budget", json=payload)
    assert response.status_code == 200
    data = response.json()
    assert data["success"] is True

    years_total = sum(y["forecast_budget"] for y in data["years"])
    # Allow tiny floating-point difference
    assert abs(data["total_forecast_budget"] - years_total) < 0.01


# ---------------------------------------------------------------------------
# validate_training_data unit tests
# ---------------------------------------------------------------------------

def test_validate_passes_with_sufficient_data():
    records = _make_training_records(20)
    valid, code, _ = validate_training_data(records)
    assert valid is True
    assert code == ""


def test_validate_fails_with_single_year():
    records = [
        {
            "acquisition_year": 2020,
            "category_id": 1,
            "category_name": "เครื่องคอมพิวเตอร์",
            "acquisition_value": 25000.0,
        }
        for _ in range(15)
    ]
    valid, code, _ = validate_training_data(records)
    assert valid is False
    assert code == "INSUFFICIENT_YEAR_DIVERSITY"
