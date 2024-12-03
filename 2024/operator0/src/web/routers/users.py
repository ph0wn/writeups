from fastapi import APIRouter,Depends, HTTPException, Request, Response
from fastapi.responses import JSONResponse
from typing import Annotated
from ..auth import *
import requests
import json
import logging

logging.basicConfig(level=logging.INFO, format='%(asctime)s - %(name)s - %(levelname)s - %(message)s')
logger = logging.getLogger(__name__)

requests.packages.urllib3.disable_warnings()

router = APIRouter(
    prefix="/users",
    tags=["users"],
    responses={404: {"description": "Not found"}},
)