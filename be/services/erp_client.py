import httpx
from config import settings

class ERPClient:
    def __init__(self):
        self.base_url = settings.erp_base_url
        self.headers  = {"Authorization": f"Bearer {settings.erp_api_key}"}

    async def get_sales_orders(self, page: int = 1):
        async with httpx.AsyncClient() as client:
            r = await client.get(f"{self.base_url}/sales-orders",
                headers=self.headers, params={"page": page})
            r.raise_for_status()
            return r.json()

    async def get_customers(self):
        async with httpx.AsyncClient() as client:
            r = await client.get(f"{self.base_url}/customers", headers=self.headers)
            r.raise_for_status()
            return r.json()

    async def get_items(self):
        async with httpx.AsyncClient() as client:
            r = await client.get(f"{self.base_url}/items", headers=self.headers)
            r.raise_for_status()
            return r.json()

erp_client = ERPClient()