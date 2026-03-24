# app.py
import contextlib
from starlette.applications import Starlette
from starlette.routing import Mount, Route
from starlette.responses import JSONResponse

from server import mcp

@contextlib.asynccontextmanager
async def lifespan(app: Starlette):
    # Required when mounting, so the session manager runs
    async with mcp.session_manager.run():
        yield

#async def health(_request):
#    return JSONResponse({"status": "healthy"})

app = Starlette(
    routes=[
        #Route("/health", health, methods=["GET"]),
        # Mount the MCP Streamable HTTP ASGI app (default endpoint is /mcp under this mount)
        Mount("/", app=mcp.streamable_http_app()),
    ],
    lifespan=lifespan,
)
