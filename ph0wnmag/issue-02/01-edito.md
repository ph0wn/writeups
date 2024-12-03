---
title: Ph0wn eMagazine, issue \#02, rev 02
subtitle: https://ph0wn.org, November 2024
lang: "en"
titlepage: true
logo: "../org/logo/logo-ph0wn.png"
titlepage-rule-color: "A31D21"
colorlinks: true
toc-own-page: true
header-includes:
- |
  ```{=latex}
  \usepackage{tcolorbox}

  \newtcolorbox{info-box}{colback=cyan!5!white,arc=0pt,outer arc=0pt,colframe=cyan!60!black}
  \newtcolorbox{warning-box}{colback=orange!5!white,arc=0pt,outer arc=0pt,colframe=orange!80!black}
  \newtcolorbox{error-box}{colback=red!5!white,arc=0pt,outer arc=0pt,colframe=red!75!black}
  ```

pandoc-latex-environment:
  tcolorbox: [box]
  info-box: [info]
  warning-box: [warning]
  error-box: [error]
...

# Edito

We are proud to present the *Second Edition of Ph0wnMag*. 
The initial goal of this eZine was to satisfy curiosity of some desperate Ph0wn participants, who had searched for hours on a challenge and unfortunately failed. Ph0wnMag goes beyond that, of course, because reading a CTF writeup is sharing knowledge, and also because we starting featuring writeups of other CTF challenges which match Ph0wn's themes.

Ph0wn 2024 was *challenging* for us in many reasons, but our retribution is to see participants work on our challenges, learn, talk, meet other hackers and want to do it again :)

Editorials exist for anecdotes. Let me share a few on our Test Sessions.

**Prepare to Qualify**

"I played so much driving games on my 386, that one day, I was cookie pasta for lunch, started to play... and realized I was cooking 2 hours later!"

**Defend**

- **Brehima**: Cryptax, why doesn't my fix code pass your exploit scripts? What have I missed?
- **Cryptax**: Hmm. Let me check... Hmm... Honestly, I don't understand, you seem to have fixed it, but I still get the flag... Let me ask Az0x.
- **Az0x**: Indeed it's fixed. Ha ha, sure you just messed up the firmware upload!
- **Cryptax**: No, no, we doubled checked. It's flashed okay. We even changed the version to check.

*2 days later*

- **Cryptax**: I need to confess the issue is caused by my exploit script. I test the backdoor exists for compliance verification. It prints the flag. That's normal. Except then my exploit script thinks it succeeded :D

**Pico Wallet**

- **RMalmain**: I'll buy a beer to anyone who gets Flag 2 without first getting Flag 1.

*20 minutes later*

- **Cryptax**: Hmm. Does it count if I retrieved 9/10th of Flag 2 that way? (showing the mechanism)
- **RMalmain**: Arg! I need to fix that!
- **Cryptax**: That's cheating! I deserved my beer!

We hope you had lots of fun at Ph0wn. If you couldn't attend this year, we hope to see you at the next edition. Enjoy the writeups and keeps bytes flowing! *Kudos to Phil242 who couldn't make it this year*.

-- Cryptax
