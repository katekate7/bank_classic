FROM node
RUN mkdir next
COPY . var/next
WORKDIR /var/next
RUN npm install
COPY . .
RUN npm run build
EXPOSE 4173
CMD ["npm", "run", "preview", "--", "--host"]