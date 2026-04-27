from fastapi import APIRouter, BackgroundTasks
from services.erp_client import erp_client
from services.laravel_client import laravel_client
from transformers.so_transformer import transform
import logging

router = APIRouter()
logger = logging.getLogger(__name__)

async def do_sync():
    try:
        raw    = await erp_client.get_sales_orders()
        orders = raw.get("data", raw) if isinstance(raw, dict) else raw
        transformed = [transform(o) for o in orders]
        result = await laravel_client.sync_sales_orders(transformed)
        logger.info(f"[SO Sync] {result}")
        return result
    except Exception as e:
        logger.error(f"[SO Sync] Error: {e}")
        raise

@router.post("/sales-orders")
async def sync_sales_orders(background_tasks: BackgroundTasks):
    background_tasks.add_task(do_sync)
    return {"message": "Sync triggered in background"}

@router.post("/sales-orders/now")
async def sync_sales_orders_now():
    return await do_sync()