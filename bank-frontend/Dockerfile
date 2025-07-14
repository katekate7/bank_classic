# Base stage
FROM node:20-alpine as base
WORKDIR /app

# Copy package files
COPY package*.json ./

# Production stage
FROM base as production
RUN npm ci --only=production
COPY . .
EXPOSE 4173
CMD ["npm", "run", "dev", "--", "--host"]

# Test stage
FROM base as test
# Install all dependencies including dev dependencies
RUN npm ci
COPY . .

# Run tests
CMD ["npm", "test"]