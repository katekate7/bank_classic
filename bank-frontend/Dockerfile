FROM node
RUN mkdir next
COPY . var/next
WORKDIR /var/next
RUN npm install
COPY . .
EXPOSE 4173
CMD ["npm", "run","dev", "preview", "--", "--host"]