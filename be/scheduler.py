from apscheduler.schedulers.asyncio import AsyncIOScheduler
from config import settings
import logging

logger = logging.getLogger(__name__)

def start_scheduler():
    from routers.sync_so import do_sync as sync_so
    from routers.sync_customer import do_sync as sync_customers
    from routers.sync_item import do_sync as sync_items

    scheduler = AsyncIOScheduler()

    scheduler.add_job(sync_so,        'interval', minutes=settings.sync_interval_minutes, id='sync_so')
    scheduler.add_job(sync_customers, 'interval', minutes=30, id='sync_customers')
    scheduler.add_job(sync_items,     'interval', minutes=60, id='sync_items')

    scheduler.start()
    logger.info(f"[Scheduler] Started — SO sync every {settings.sync_interval_minutes} min")