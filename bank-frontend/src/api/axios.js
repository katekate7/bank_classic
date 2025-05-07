// src/api/axios.js
import axios from 'axios';

const api = axios.create({
  baseURL: 'http://localhost:8000', // Symfony бекенд
  withCredentials: true,            // Дозволяє відправляти cookie (сесії)
});

export default api;