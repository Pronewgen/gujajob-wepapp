from __future__ import annotations

import logging
from contextlib import asynccontextmanager

from fastapi import FastAPI
from fastapi.responses import JSONResponse

from app.schemas import ForecastRequest
from app.services.replacement_budget_forecast import run_forecast

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)


@asynccontextmanager
async def lifespan(app: FastAPI):  # noqa: ARG001
    yield


app = FastAPI(
    title="GUJAJOB AI Forecast Service",
    version="1.0.0",
    lifespan=lifespan,
)


@app.get("/health")
def health():
    return {"status": "ok"}


@app.post("/forecast/replacement-budget")
async def forecast_replacement_budget(payload: ForecastRequest):
    # Empty candidate list — return zero-budget result without calling the model
    if not payload.candidate_assets:
        return JSONResponse(
            {
                "success": True,
                "forecast_years": payload.forecast_years,
                "total_assets": 0,
                "total_forecast_budget": 0.0,
                "years": [],
                "assets": [],
                "model": {
                    "name": "Ridge Regression",
                    "training_records": len(payload.training_records),
                    "mae": None,
                    "mape": None,
                },
            }
        )

    try:
        result = run_forecast(
            forecast_years=payload.forecast_years,
            training_records=[r.model_dump() for r in payload.training_records],
            candidate_assets=[a.model_dump() for a in payload.candidate_assets],
        )
    except Exception as exc:
        logger.error("Unexpected forecast error: %s", exc, exc_info=True)
        return JSONResponse(
            {
                "success": False,
                "code": "MODEL_ERROR",
                "message": "เกิดข้อผิดพลาดในการพยากรณ์",
            },
            status_code=500,
        )

    if not result.get("success"):
        return JSONResponse(result, status_code=422)

    return JSONResponse(result)
