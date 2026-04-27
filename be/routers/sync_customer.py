from fastapi import APIRouter
import logging

router = APIRouter()
logger = logging.getLogger(__name__)

async def do_sync():
    logger.info("[Customer Sync] Triggered (Stub)")
    return {"message": "Customer sync stub executed"}

@router.post("/customers")
async def sync_customers():
    return await do_sync()