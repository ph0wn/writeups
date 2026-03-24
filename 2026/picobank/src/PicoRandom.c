#include "stdbool.h"
#include "PicoRandom.h"
#include "PicoCommon.h"

#ifndef PICO_LINUX
#include "fsl_lpadc.h"
#include "fsl_clock.h"
#include "fsl_port.h"
#include "mcuxCsslFlowProtection.h"
#include "mcuxCsslFlowProtection_FunctionIdentifiers.h"

#include <mcuxClEls.h>
#include <mcuxCsslFlowProtection.h>
#include <mcuxClCore_FunctionIdentifiers.h>
#endif

static bool is_initialized = false;

bool PicoRandomIsInitialized()
{
	return is_initialized;
}

#ifdef PICO_LINUX
void PicoRandomInit()
{
	is_initialized = true;
}

uintptr_t PicoRandomGet()
{
    uintptr_t val;

	if (!is_initialized) {
		PPRINTF("Error: prng not initialized.");
		PicoWfe();
	}

    if (getrandom(&val, sizeof(val), 0) != sizeof(val)) {
        PDEBUG("Error: could not generate random value");
        PicoWfe();
    }

    return val;
}
#else

void PicoRandomInit()
{
    if (is_initialized) {
        return;
    }

    PPRINTF("[PicoRandom] Initializing...");

    MCUX_CSSL_FP_FUNCTION_CALL_BEGIN(result, token, mcuxClEls_Enable_Async());
    if ((MCUX_CSSL_FP_FUNCTION_CALLED(mcuxClEls_Enable_Async) != token) || (MCUXCLELS_STATUS_OK_WAIT != result)) {
        PicoWfe();
    }
    MCUX_CSSL_FP_FUNCTION_CALL_END();

    MCUX_CSSL_FP_FUNCTION_CALL_BEGIN(result, token, mcuxClEls_WaitForOperation(MCUXCLELS_ERROR_FLAGS_CLEAR));
    if ((MCUX_CSSL_FP_FUNCTION_CALLED(mcuxClEls_WaitForOperation) != token) || (MCUXCLELS_STATUS_OK != result)) {
        PicoWfe();
    }
    MCUX_CSSL_FP_FUNCTION_CALL_END();

    MCUX_CSSL_FP_FUNCTION_CALL_BEGIN(result, token, mcuxClEls_Reset_Async(MCUXCLELS_RESET_DO_NOT_CANCEL));
    if ((MCUX_CSSL_FP_FUNCTION_CALLED(mcuxClEls_Reset_Async) != token) || (MCUXCLELS_STATUS_OK_WAIT != result)) {
        PicoWfe();
    }
    MCUX_CSSL_FP_FUNCTION_CALL_END();

    MCUX_CSSL_FP_FUNCTION_CALL_BEGIN(result, token, mcuxClEls_WaitForOperation(MCUXCLELS_ERROR_FLAGS_CLEAR));
    if ((MCUX_CSSL_FP_FUNCTION_CALLED(mcuxClEls_WaitForOperation) != token) || (MCUXCLELS_STATUS_OK != result)) {
        PicoWfe();
    }
    MCUX_CSSL_FP_FUNCTION_CALL_END();

    MCUX_CSSL_FP_FUNCTION_CALL_BEGIN(result, token, mcuxClEls_KeyDelete_Async(18));
    if ((MCUX_CSSL_FP_FUNCTION_CALLED(mcuxClEls_KeyDelete_Async) != token) || (MCUXCLELS_STATUS_OK_WAIT != result)) {
        PicoWfe();
    }
    MCUX_CSSL_FP_FUNCTION_CALL_END();

    MCUX_CSSL_FP_FUNCTION_CALL_BEGIN(result, token, mcuxClEls_WaitForOperation(MCUXCLELS_ERROR_FLAGS_CLEAR));
    if ((MCUX_CSSL_FP_FUNCTION_CALLED(mcuxClEls_WaitForOperation) != token) || (MCUXCLELS_STATUS_OK != result)) {
        PicoWfe();
    }
    MCUX_CSSL_FP_FUNCTION_CALL_END();

    uint32_t seed = 0;
    MCUX_CSSL_FP_FUNCTION_CALL_BEGIN(result, token, mcuxClEls_Prng_GetRandom((uint8_t *)&seed, sizeof(seed)));
    if ((MCUX_CSSL_FP_FUNCTION_CALLED(mcuxClEls_Prng_GetRandom) != token) || (MCUXCLELS_STATUS_OK != result)) {
        PicoWfe();
    }
    MCUX_CSSL_FP_FUNCTION_CALL_END();

    PDEBUG("seed: 0x%x", seed);
    srand(seed);
    is_initialized = true;

    PPRINTF("[PicoRandom] Initialized.");
}

uintptr_t PicoRandomGet()
{
	if (!is_initialized) {
		PPRINTF("Error: PicoRandom not initialized.");
		PicoWfe();
	}

    return rand();
}
#endif
