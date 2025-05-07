FROM jenkins/inbound-agent
USER root
RUN apt update
RUN apt install -y curl
# Install Docker
RUN curl -fsSL https://get.docker.com -o get-docker.sh
RUN sh get-docker.sh
# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt install -y nodejs