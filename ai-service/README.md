# GUJAJOB AI Forecast Service

บริการพยากรณ์งบประมาณจัดซื้อครุภัณฑ์ทดแทน  
ใช้ **Ridge Regression** (scikit-learn) ในการพยากรณ์มูลค่าทดแทนตามหมวดครุภัณฑ์และปีที่คาดว่าจะถึงกำหนดทดแทน

---

## ความต้องการระบบ

| รายการ | เวอร์ชัน |
|--------|---------|
| Python | 3.10 ขึ้นไป |
| pip    | 23+      |

---

## การติดตั้ง (Windows)

```powershell
# 1. สร้าง virtual environment
cd ai-service
python -m venv .venv

# 2. ติดตั้ง packages
.venv\Scripts\python.exe -m pip install --upgrade pip
.venv\Scripts\python.exe -m pip install -r requirements.txt
```

> หากพบ Execution Policy Error บน PowerShell:
> ```powershell
> Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
> ```

---

## การติดตั้ง (macOS / Linux)

```bash
cd ai-service
python3 -m venv .venv
.venv/bin/pip install --upgrade pip
.venv/bin/pip install -r requirements.txt
```

---

## เปิดใช้งาน AI Service

### Windows
```powershell
cd ai-service
.venv\Scripts\python.exe -m uvicorn app.main:app --host 127.0.0.1 --port 8001 --reload
```

### macOS / Linux
```bash
cd ai-service
.venv/bin/python -m uvicorn app.main:app --host 127.0.0.1 --port 8001 --reload
```

เมื่อเปิดสำเร็จจะเห็นข้อความ:
```
INFO:     Uvicorn running on http://127.0.0.1:8001 (Press CTRL+C to quit)
```

ทดสอบ Health:
```
GET http://127.0.0.1:8001/health
```

---

## การทดสอบ (pytest)

```powershell
# Windows
.venv\Scripts\python.exe -m pytest tests/ -v

# macOS / Linux
.venv/bin/python -m pytest tests/ -v
```

---

## Environment Variables (Laravel)

เพิ่มใน `.env` ของ Laravel:

```env
AI_SERVICE_URL=http://127.0.0.1:8001
AI_SERVICE_TIMEOUT=30
AI_FORECAST_DEMO=false
```

---

## API Endpoints

### `GET /health`
```json
{"status": "ok"}
```

### `POST /forecast/replacement-budget`

**Request:**
```json
{
    "forecast_years": 3,
    "training_records": [
        {"acquisition_year": 2020, "category_id": 1, "category_name": "เครื่องคอมพิวเตอร์", "acquisition_value": 26500}
    ],
    "candidate_assets": [
        {
            "asset_id": 1,
            "asset_code": "ASS-0001",
            "asset_name": "เครื่องคอมพิวเตอร์โน้ตบุ๊ก",
            "category_id": 1,
            "category_name": "เครื่องคอมพิวเตอร์",
            "organization_id": 10,
            "organization_name": "สำนักงานกลาง",
            "acceptance_date": "15/01/2020",
            "current_value": 5000.00,
            "forecast_year": 2027
        }
    ]
}
```

---

## ข้อมูล Demo (สำหรับทดสอบเท่านั้น)

ไฟล์ `data/demo_training.csv` มีข้อมูลตัวอย่าง **ห้ามใช้ในระบบ Production**  
เปิดใช้ demo mode ได้โดยตั้งค่า `AI_FORECAST_DEMO=true` ใน `.env` ของ Laravel  
ค่า default คือ `false`

---

## โมเดล

| รายการ | รายละเอียด |
|--------|-----------|
| Algorithm | Ridge Regression |
| Library | scikit-learn Pipeline |
| Features | `acquisition_year` (numeric), `category_name` (OneHotEncoded) |
| Target | `acquisition_value` (มูลค่าที่ได้มา) |
| Evaluation | Time-based split — ปีเก่า = train, ปีล่าสุด = test |
| Metrics | MAE, MAPE |
