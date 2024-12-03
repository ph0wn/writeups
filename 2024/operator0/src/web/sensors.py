from pydantic import BaseModel

class SensorsData(BaseModel):
    temperature: float = 0.0
    windDirection: str = ""
    windSpeed: float = 0.0
    humidity: float = 0.0
    pressure: float = 0.0

    def __init__(self, temperature: float, windDirection: str, windSpeed: float, humidity: float, pressure: float):
        super().__init__()
        self.temperature = temperature
        self.windDirection = windDirection
        self.windSpeed = windSpeed
        self.humidity = humidity
        self.pressure = pressure
        
