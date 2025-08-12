# Build the app

You must be in the directory:  `./sychallenges/android-sys/app`

## Pre-requisites

1. Install [gradle >=4.1](https://gradle.org/)

2. Install gradle wrapper:

```
$ gradle wrapper
```


## Build

1. Modify local.properties to point to your Android SDK

```
sdk.dir=/opt/android-sdk-linux/
```

2. Set your JAVA HOME to a valid **JDK**

```
export JAVA_HOME=/opt/java/jdk1.8.0_40
```

3. Unset your ANDROID_NDK_HOME or [set it to an Android NDK you have if you encounter this bug](https://stackoverflow.com/questions/42682357/how-to-fix-android-studios-2-3-ndk-bug)

4. **Build the app**

```
$ gradle build
```

You should obtain two apps:

- ./app/build/outputs/apk/app-debug.apk
- ./app/build/outputs/apk/app-release.apk


# Starting Android docker container

1. Start docker.
2. Pull the container
```
$ docker pull cryptax/android-re
```
3. Start the container. If a similar container is already started, you may need to make docker rm commands, with docker ps to know the id of running containers
```
$ docker run -d -p 5022:22 -p 5900:5900 --name android-docker android/ssecond:test
```
4. Connect to the docker. Start vncviewer (or via ssh), and connect to `localhost:5900`. Password is *rootpass*.
Via ssh:
ssh -p 5022 root@127.0.0.1

5. If in VNC mode (graphical mode), make a right click in order to start a terminal, and then
6. Start the Android emulator as follows:
```
$ emulator5 &
```
ou
```
$ emulator7 &
```

7. Putting the compile file
```
$docker cp build/outputs/apk/app-release.apk 2ff002f37686:tmp/
```
(use the right docker id, the one you obtain when starting the docker image)

8. Starting the app, then
adb shell
su
atrace irq


8.Docker export
docker export 3887cdfadd89 > mytestdocker.tar

9. Docker import
