import httpx
from config import settings

class LaravelClient:
    def __init__(self):
        self.base_url = settings.laravel_base_url
        self.headers  = {
            "X-Integration-Key": settings.laravel_integration_key,
            "Content-Type":      "application/json",
            "Accept":            "application/json",
        }

    async def sync_sales_orders(self, data: list):
        async with httpx.AsyncClient(timeout=30) as client:
            r = await client.post(
                f"{self.base_url}/api/integration/sync/sales-orders",
                headers=self.headers,
                json={"data": data}
            )
            r.raise_for_status()
            return r.json()

    async def sync_customers(self, data: list):
        async with httpx.AsyncClient(timeout=30) as client:
            r = await client.post(
                f"{self.base_url}/api/integration/sync/customers",
                headers=self.headers, json={"data": data}
            )
            r.raise_for_status()
            return r.json()

    async def sync_items(self, data: list):
        async with httpx.AsyncClient(timeout=30) as client:
            r = await client.post(
                f"{self.base_url}/api/integration/sync/items",
                headers=self.headers, json={"data": data}
            )
            r.raise_for_status()
            return r.json()

laravel_client = LaravelClient()