# OSINT by Ludoze, Cryptax and Boguette

This challenge was created by *Ludoze* and *Cryptax*. The write-up was created from *Boguette*'s feedback and written by *Cryptax*.


## Description

This is a Hardware OSINT challenge: we have a PCB and must figure out to what device it corresponds. To flag, one must discover the **common name** of the device and its **product name**.

```
Your task is to uncover the common name of the device and its commercial name, both in lowercase.

For instance, if this card belonged to a Tesla, the flag would be:
`ph0wn{car_tesla}`
```

![](./images/IMG_9241.JPG)

## Investigation

::: error

**ERRATUM**

This write-up was written with a *slightly different description where we asked the name of the company*. We changed afterwards to the *common name* of the device.

Indeed, the board you saw was *not* designed by the company *Ring*, as this company did not exist at that time. 

We decided to change the description to ask instead for the common name, which was a *ring* (you could have tried indeed as well *bell* or *door bell* - synonyms). A *ring* happens also to be the name of the company who bought the makers of the DoorBot a few years after, but it's a *coincidence*, and normally the format asking for a *common name* and a *product name* was correct.

In this write-up, please consider the description was still "company name + product name"

:::

On one side, we see the name GainSpan, and the chip's reference: GS1011MEE.
Boguette searched on Internet a reference.

![](./images/search-gainspan.png)

Among the results, there is an article on [DoorBot](https://www.geeek.org/doorbot-test-avis-944/). The article gives lots of additional information, and points to a URL on www.getdoorbot.com which no longer exists.

So, we use [WayBack Machine to access the page](https://web.archive.org/web/20140625090124/http://www.getdoorbot.com/pages/terms-of-use).

Besides, any request to www.getdoorbot.com usually redirects now to ring.com

![](./images/ring.png)

So the company name is *Ring*, and the product name is *Doorbot*.
The flag is `ph0wn{ring_doorbot}`.

\newpage
