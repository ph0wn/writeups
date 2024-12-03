# Running the container

You need a `.env` file which contains your Discord key:

```
DISCORD_TOKEN=CENSORED
```

Then, `docker compose build`, `docker compose up -d`.

The Picobot is currently running on IP ADDRESS and connects to

- #test-tubblebot of Ph0wn Organizers Discord server (restricted to organizers)


# References

- [Discord.py documentation and Getting Started](https://discordpy.readthedocs.io/en/stable/)
- [Creating a Bot Account](https://discordpy.readthedocs.io/en/stable/discord.html)
- [Inviting your Bot](https://discordpy.readthedocs.io/en/stable/discord.html#inviting-your-bot): 

        - select bot
		- Privileged Gateway Intents: Message Content Intent
        - Send Messages
        - Add Reactions
        - Read Message History
        - Read Messages/View Channels
        - Embed Links
		- Attach Files
		
		
