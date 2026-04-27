from fastapi import FastAPI
from contextlib import asynccontextmanager
from scheduler import start_scheduler
from routers import sync_so, sync_customer, sync_item

@asynccontextmanager
async def lifespan(app: FastAPI):
    start_scheduler()
    yield

app = FastAPI(title="Logistics ERP Middleware", version="1.0.0", lifespan=lifespan)

app.include_router(sync_so.router,       prefix="/sync", tags=["Sales Orders"])
app.include_router(sync_customer.router, prefix="/sync", tags=["Customers"])
app.include_router(sync_item.router,     prefix="/sync", tags=["Items"])

@app.get("/health")
def health(): return {"status": "ok"}