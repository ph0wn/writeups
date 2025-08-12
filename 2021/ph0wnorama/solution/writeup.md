TODO: Not sure this writeup is still valid

# Ph0wnorama

We go on http://127.0.0.1:1233. This web site asks a fingerprint image to log in.

![](./webclient.png)

We do a few attempts. For example, copy the fingerprint image and try to login as `admin` with that image: it complains the image is too big, only 50Ko max. We scale it down, and login fails.

## Analysis of server's code


We have a look at the provided source code. We see it communicates with a remote server on port 1234.

```python
username = request.json["username"]
...
fingerprint = request.json["fingerprint"]
```

We see it will lookup the username in a `database`. Apparently, in the database, we already have `capitanLamer` and `orro`. But `admin` is also referenced.

```python
database['capitanLamer'] = ...
database['orro'] = ...
...
with open(minute) as f:
           database['admin'] = json.load(f)
...
userFingerprint = database[username]
```

The authentication mechanism consists in comparing the fingerprint we supply (`fingerprint`) with the one stored in the database (`userFingerprint`).
Fingerprints are lists of point coordinates. Comparing fingerprints consists in comparing the distance between a list of point in the supplied fingerprint and the stored fingerprint. If the cumulated distance is small, access is granted.

```python
for userSuppliedPoint,storedPoint in zip(userSupplied,stored):
   currentDistance = calculateDistance(userSuppliedPoint, storedPoint)
   hint+=str(currentDistance)+";"
   totalDistance+=currentDistance
   if totalDistance<=1:
       if username=='admin':
           hint=flag
```

The expected fingerprints for *capitanLamer* and *orro* are provided. We need to login as `admin` to get the flag. We don't have the expected fingerprint for that user. It is located in subdirectories we don't have access to on the server. Moreover, the fingerprint for `admin` actually changes every 2 minute.

```python
minute = "adminSaliences/"+str(round(datetime.datetime.now().minute/2))
with open(minute) as f:
    database['admin'] = json.load(f)
```    

Moreover, the code shows 2 other interesting things:

1. We can get debug messages if we supply a hard coded cookie (` if request.cookies.get('RGVidWdnaW5n') == 'VHJ1ZQ==':`)
2. IP Ban is in place to limit brute forcing: `ip_ban = IpBan(ban_seconds=110, ban_count=4)`

## Towards the solution

We are going to implement a program that sends HTTP request to the server with:

- The hard coded cookie
- A JSON object with username `admin` + we need to compute a fingerprint the server accepts

The number of points the server checks varies with time. If we don't supply the correct number of points, the server send back a message like `You need 50 salient points`.

As we cannot guess how many points we will need, our program will send initially no points, ,read the debug message to know how many points we should construct.

Then, we need to find the correct coordinates for x points. When we supply a given fingerprint, the server sends back the distance between this point and the solution point. But we do not know in which direction the solution is.

So? How can we head towards the solution point? We could step by step try to go in a given direction and check with distance if we are getting closer or not. But this is going to need lots of requests and we will get banned.

The solution is geometric. We know the distance from a given point A. So we know that the real point is with a circle centered on A and whose radius is the distance. If we know compute the distance from another point B, we get another circle. The solution is at the intersection of the circles! (provided they intersect). There are normally 2 intersections, we don't know which one is the correct one, but we can try one - arbitrarily - and if it's not the correct one we'll shift to the other intersection.

For that we need:

- 1 request to get the number of points
- 1 request to get distance from point A
- 1 request to get distance from point B
- 1 request to test with first set of intersections
- 1 final request adjusting bad intersections

That's 5 requests, should not get banned.

The formula to get intersections between 2 circles can be found online. The formula is greatly simplified if we select point a with coordinates x=0 and y=0.

## Solution

```
[+] we need 42 salient points
Get distances from (0,0) ... 
Get distances from another point ...
Compute the intersections...
Get distances using first intersection...
Distance sum:  4227.084337933184
Solution: 
Successfully logged in Ph0wn{G3omeTry_1s_4lway5_Us3Ful}
```
