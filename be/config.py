from pydantic_settings import BaseSettings

class Settings(BaseSettings):
    erp_base_url: str      = "http://localhost:8000/api"
    erp_api_key: str       = "dummy-key"
    laravel_base_url: str  = "http://localhost:8001/api"
    laravel_integration_key: str = "dummy-key"
    sync_interval_minutes: int   = 5

    class Config:
        env_file = ".env"

settings = Settings()