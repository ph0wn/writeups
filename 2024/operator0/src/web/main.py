
from .routers import users
from .auth import *
from .sensors import *
from datetime import datetime, timedelta, timezone
from typing import Annotated
import logging



from fastapi import Depends, FastAPI, HTTPException, status, Request
from fastapi.responses import HTMLResponse, PlainTextResponse
from fastapi.security import OAuth2PasswordBearer, OAuth2PasswordRequestForm
from fastapi import APIRouter
from fastapi.staticfiles import StaticFiles

import jwt
from jwt.exceptions import InvalidTokenError
from passlib.context import CryptContext
from pydantic import BaseModel
import random


logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

app = FastAPI()


app.include_router(users.router,)


# Mount the static files directory
app.mount("/static", StaticFiles(directory="static"), name="static")

@app.get("/", response_class=HTMLResponse)
async def root():
    with open("index.html", "r") as file:
        html_content = file.read()
    return HTMLResponse(content=html_content)

@app.get("/robots.txt", response_class=PlainTextResponse)
async def robots_txt():
    content = """
    User-agent: *
    Disallow: /docs
    Disallow: /redoc
    """
    return content



@app.post("/token")
async def login_for_access_token(form_data: Annotated[OAuth2PasswordRequestForm, Depends()],) -> Token:
    user = authenticate_user(users_db, form_data.username, form_data.password)
    if not user:
        raise HTTPException(
            status_code=status.HTTP_401_UNAUTHORIZED,
            detail="Incorrect username or password",
            headers={"WWW-Authenticate": "Bearer"},
        )
    access_token_expires = timedelta(minutes=ACCESS_TOKEN_EXPIRE_MINUTES)
    access_token = create_access_token(
        data={"sub": user.username}, expires_delta=access_token_expires
    )
    return Token(access_token=access_token,token_type="bearer")

@app.get("/me", response_model=User)
async def read_users_me(current_user: Annotated[User, Depends(get_current_active_user)],):
    return current_user

@app.get("/users/{userId}", response_model=UserInDB)
async def read_user(userId: int, current_user: Annotated[User, Depends(get_current_active_user)],):
    if userId in users_db:
        user_dict = users_db[userId]
        return UserInDB(**user_dict)
  
    raise HTTPException(status_code=404, detail="User not found")


@app.get("/sensors", response_model=SensorsData)
async def read_sensors_data(current_user: Annotated[User, Depends(get_current_active_user)],):
    randomTemperature = random.randint(20, 50)
    randomWindDirection = random.choice(["N", "NE", "E", "SE", "S", "SW", "W", "NW"])
    randomWindSpeed = random.randint(55, 100)
    randomHumidity = random.randint(0, 100)
    randomPressure = random.randint(900, 1100)
    sensorData = SensorsData(temperature=randomTemperature, windDirection=randomWindDirection, windSpeed=randomWindSpeed, 
                             humidity=randomHumidity, pressure=randomPressure)
    return sensorData
