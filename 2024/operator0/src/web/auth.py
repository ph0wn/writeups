from datetime import datetime, timedelta, timezone
from typing import Annotated
import logging


from fastapi import Depends, FastAPI, HTTPException, status, Request
from fastapi.security import OAuth2PasswordBearer, OAuth2PasswordRequestForm
from fastapi import APIRouter

import jwt
from jwt.exceptions import InvalidTokenError
from passlib.context import CryptContext
from pydantic import BaseModel
from  .database import databaseUsers as users_db

#TODO: later move to a config file for production
SECRET_KEY = "ae3b1cca74a98274063a381fd87bc5216d2e93d7ef0c032db6485f930a54f0b1"
ALGORITHM = "HS256"
ACCESS_TOKEN_EXPIRE_MINUTES = 30



class Token(BaseModel):
    access_token: str
    token_type: str

class TokenData(BaseModel):
    username: str | None = None

class User(BaseModel):
    id: int
    username: str
    plain_password: str
    accessrole: str
    disabled: bool | None = False

class UserInDB(User):
    plain_password: str
    notices: str

class SensorsData(BaseModel):
    temperature: float
    windDirection: float
    windSpeed: float
    humidity: float
    pressure: float



logger = logging.getLogger(__name__)
pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")
oauth2_scheme = OAuth2PasswordBearer(tokenUrl="token")

def verify_password(plain_password: str, hashed_password: str):
    return plain_password==hashed_password #pwd_context.verify(plain_password, hashed_password)


def get_user(db, username:str):
    for user in db.values():
        if user["username"] == username:
            user_dict = user
            return User(**user_dict)
    

def authenticate_user(users_db, username:str, password:str):
    user = get_user(users_db,username)
    if not user:
        return False
    if not verify_password(password,user.plain_password):
        return False
    if user.disabled:
        return False
    return user

def create_access_token(data:dict, expires_delta: timedelta | None = None):
    to_encode = data.copy()
    if expires_delta:
        expire = datetime.now(timezone.utc) + expires_delta
    else:
        expire = datetime.now(timezone.utc) + timedelta(minutes=15)
    
    to_encode.update({"exp":expire})
    encoded_jwt = jwt.encode(to_encode, SECRET_KEY, algorithm = ALGORITHM)
    return encoded_jwt

async def get_current_user(token: Annotated[str, Depends(oauth2_scheme)]):
    credentials_exception = HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail="Invalid credentials",
        headers={"WWW-Authenticate": "Bearer"},
    )

    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        username : str = payload.get("sub")
        if username is None:
            raise credentials_exception
        token_data = TokenData(username=username)
    except InvalidTokenError:
        raise credentials_exception
    user = get_user(users_db, username=token_data.username)
    if user is None:
        raise credentials_exception
    return user

async def get_current_active_user(current_user: Annotated[User, Depends(get_current_user)]):
    if current_user.disabled:
        raise HTTPException(status_code=400, detail="Inactive user")
    return current_user
