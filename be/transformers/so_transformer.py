def transform(erp_order: dict) -> dict:
    return {
        "erp_id":          erp_order.get("id"),
        "so_number":       erp_order.get("order_number") or erp_order.get("so_number"),
        "customer_erp_id": erp_order.get("customer_id"),
        "delivery_date":   erp_order.get("delivery_date") or erp_order.get("ship_date"),
        "items": [
            {
                "item_erp_id": item.get("product_id") or item.get("item_id"),
                "qty":         float(item.get("quantity") or item.get("qty") or 0),
            }
            for item in erp_order.get("lines", erp_order.get("items", []))
        ]
    }