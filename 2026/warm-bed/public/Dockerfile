FROM debian:latest

RUN apt-get update && apt-get install -y \
    libx11-6 libxext6 libxrender1 libxcb1 libx11-xcb1 libxtst6 libfuse2 libegl1 libgles2 mesa-utils \
    && rm -rf /var/lib/apt/lists/*

# Copier l'AppImage dans le conteneur
COPY WarmBedSimulator-1.0.0-x86_64.AppImage /app/WarmBedSimulator.AppImage

# Rendre exécutable
RUN chmod u+x /app/WarmBedSimulator.AppImage

# Définir l'affichage graphique (X11 forwarding)
ENV DISPLAY=:0

# Point d'entrée
CMD ["/app/WarmBedSimulator.AppImage"]
