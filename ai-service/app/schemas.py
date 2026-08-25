from __future__ import annotations

from typing import Optional

from pydantic import BaseModel, Field


class TrainingRecord(BaseModel):
    acquisition_year: int
    category_id: int
    category_name: str
    acquisition_value: float


class CandidateAsset(BaseModel):
    asset_id: int
    asset_code: str
    asset_name: Optional[str] = None
    category_id: int
    category_name: str
    organization_id: Optional[int] = None
    organization_name: Optional[str] = None
    acceptance_date: Optional[str] = None
    current_value: Optional[float] = None
    forecast_year: int


class ForecastRequest(BaseModel):
    forecast_years: int = Field(..., ge=1, le=3)
    training_records: list[TrainingRecord]
    candidate_assets: list[CandidateAsset]


class YearForecast(BaseModel):
    year: int
    asset_count: int
    forecast_budget: float


class AssetForecast(BaseModel):
    asset_code: str
    asset_name: Optional[str] = None
    category_name: str
    organization_name: Optional[str] = None
    acceptance_date: Optional[str] = None
    forecast_year: int
    current_value: Optional[float] = None
    predicted_replacement_cost: float


class ModelInfo(BaseModel):
    name: str
    training_records: int
    mae: Optional[float] = None
    mape: Optional[float] = None
