# Operator0 Challenge Summary

## Challenge Overview
> **Challenge Details**
> - Difficulty: Medium
> - Categories: Web Exploitation, Malware Analysis
> - Skills required: API testing, Process Injection analysis, Network traffic analysis
> - Two-stage challenge involving web exploitation and malware analysis

## Stage 1 - Web Application

> **Stage 1 - Web Enumeration Summary**
> - Discovered exposed FastAPI documentation at /docs and /redoc
> - Found 6 main API endpoints through OpenAPI specification
> - Located hardcoded credentials in sensorsData.js
> - Identified authentication endpoint at /token
> - Found two key data models: User and UserInDB

> **Stage 1 - Exploitation Summary**
> - Successfully retrieved JWT token using discovered credentials
> - Found IDOR vulnerability in /users/{userId} endpoint
> - Enumerated user accounts despite "gui only" access role
> - Discovered adminCroco account with SSH access
> - Retrieved Stage 1 flag: ph0wn{stage1_picoAndAPIs_are_not_a_goodmatch?!}

## Stage 2 - Malware Analysis

> **Stage 2 - Initial Analysis Summary**
> - Identified suspicious /bin/npt process running as root
> - Analyzed binary using Ghidra
> - Found process injection functionality targeting SSH processes
> - Discovered reference to /tmp/payload.so
> - Identified PAM authentication hooking mechanism

> **Stage 2 - Payload Analysis Summary**
> - Located payload.so and .secrets.txt in /tmp directory
> - Identified credential harvesting functionality
> - Found DNS exfiltration mechanism
> - Discovered XOR encryption with key "ph0wn24Operator0X0RKey"
> - Retrieved final flag: ph0wn{kur0icroco_sh0uld_h4ve_us3d_a_m0re_subt1le_appr0ach}

## Technical Details

> **Tools Used**
> - Web Testing: curl, jq
> - Reverse Engineering: Ghidra
> - Network Analysis: tcpdump
> - Decoding: CyberChef

> **Key Vulnerabilities**
> - Exposed API documentation
> - Hardcoded credentials
> - IDOR in user endpoint
> - Process injection in SSH service
> - Weak encryption (XOR with static key)

## Attack Chain Overview

> **Complete Attack Path**
> 1. Discovered API documentation and endpoints
> 2. Found hardcoded credentials in JavaScript
> 3. Exploited IDOR to enumerate users
> 4. Retrieved SSH credentials
> 5. Accessed target system via SSH
> 6. Identified malicious process
> 7. Analyzed malware samples
> 8. Decoded exfiltrated data
> 9. Retrieved final flag

## Flags
- Stage 1: `ph0wn{stage1_picoAndAPIs_are_not_a_goodmatch?!}`
- Stage 2: `ph0wn{kur0icroco_sh0uld_h4ve_us3d_a_m0re_subt1le_appr0ach}` 