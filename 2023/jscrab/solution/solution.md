# JohannSebastianCrab by Bastien

This challenge was created by *Bastien*.

## Description

It appears that Bach has bestowed upon us a musical offering, and as Bach was known for weaving hidden messages within his compositions, perhaps you'll uncover a hidden gem amidst these musical notes. All you require is a healthy dose of curiosity and a micro:bit and its piano keyboard. May your musical exploration be rewarding!

Remember: it is a **crab** canon. And crabs take pleasure in gazing at their reflections in the shimmering sea mirror.

## Finding the original score

The metadata of `musikalischeOpfer.pdf` contains the following field: "Creator: MuseScore Version: 4.1.1". Furthermore, the original score's creator, "twoflutes," is explicitly credited at the bottom of each page. A brief investigation on the [Musescore website](https://musescore.org/) easily yields access to the original score.

## Comparing the two scores

Here, two valid strategies are possible: comparing the two scores manually or opting for an automated approach by developing an algorithm that lists the differences. 

For example, the first few measures show a few differences between the original partition and the one of Ph0wn:

![Original partition](./images/crab-original.png)
![Modified partition](./images/crab-modified.png)


The automated approach requires these preliminary steps:

1. Import `musikalischeOpfer.pdf` into MuseScore to convert it into a MuseScore project.
2. Export the resulting project as a `.musicxml`or `.mxl` file.
3. Export the original project as a `.musicxml` or `.mxl` file.

After completing these steps, the next task involves crafting a comparison algorithm that specifically targets the pitch differences between the two scores. Here is an example:

```python
fmod = open('mod.musicxml','r')
fori = open('ori.musicxml','r')
lmod = fmod.readlines()
lori = fori.readlines()
fmod.close()
fori.close()
modpitch = []
oripitch = []
output = []
i = 0
flag = False

def extractNote(index,liste):
	buff = []
	for line in liste[index:]:
		buff.append(line)
		if "</note>" in line:
			return buff

def extractPitch(liste):
	pitch = ""
	for line in liste:
		if "<step" in line:
			pitch += line.split(">")[1].split('<')[0]
		if "<accidental" in line:
			pitch += line.split(">")[1].split('<')[0]
	return pitch

for line in lmod:
	if "<note" in line:
		modpitch.append(extractPitch(extractNote(i,lmod)))
	i += 1
i = 0

for line in lori:
	if "<note" in line:
		oripitch.append(extractPitch(extractNote(i,lori)))
	i += 1

for j in range(len(modpitch)):
	if modpitch[j] != oripitch[j]:
		output.append(modpitch[j])

print(output)
```

Executing this algorithm provides the following output:

`['D', 'C', 'C', 'A', 'B', 'F', 'D', 'Enatural', 'Bnatural', 'C', 'C']`

At this point we need to compare this output to the scores. First, we notice that the two first `C` are tied: therefore they are a single note, thus we should discard the second `C`. In addition, the `B` appears here since in the original score, there was an altered (natural) B in the same measure, necessitating the restoration of its alteration to a flat. However, in the modified score, there is no altered B in the measure, making it unnecessary to add a flat that is already indicated in the key signature. Thus, we should discard the first `B`.

At the end, the two (manual or automated) approaches provide the following sequence: `D C A F D E B C C`.

## Mirroring the sequence

The hint provided in the description (Remember: it is a **crab** canon. And crabs take pleasure in gazing at their reflections in the shimmering sea mirror), coupled with a review of the Wikipedia page on crab canon, suggests that the sequence should be played forward and then reversed. Our complete sequence is then `D C A F D E B C C C C B E D F A C D`.

## Revealing the flag

Executing the entire sequence on the micro:bit's keyboard reveals the flag on the LED screen of the micro:bit: `ph0wn{w3lld0neMus1c1an}`.


\newpage

