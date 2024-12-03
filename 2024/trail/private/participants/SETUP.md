ARM64 Docker Container for ph0wn
================================
by Saumil Shah @therealsaumil

October 2024

## QUICK INSTALL STEPS

```
./build-ph0wn-volume
./build-ph0wn-docker
```

Start the main console:

```
./run-ph0wn-docker
```

The container will automatically launch QEMU and the ARM64 emulated environment immediately after it starts.

From another terminal, attach to the container:

```
./ph0wn-docker-shell
```

### Shutting down the ARM64 emulated environment

```
./shutdown-ph0wn-docker
```

