We have observed suspicious yet subtle network activity originating from PicoCorp's IoT bastion host. Our initial analysis indicates that this activity began after the deployment of PicoCorp’s new web application: http://34.155.206.38:9001, which allows race participants to track real-time weather conditions on the race circuit. Could you help us investigate and determine:

- How the attackers are gaining access? Can you replicate their entry method?
- After gaining access, what have they deployed, and why are we seeing suspicious network activity?


Additional Notes:

- The CTI team has shown a strong correlation between the suspicious network activity and the APT group known as "KuroiCroco." However, this particular group is not typically subtle in their operations.
- No logs are available for analysis, and preserving forensic evidence is not a priority—we just need to get to the bottom of this.
