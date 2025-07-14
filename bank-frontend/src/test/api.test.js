import { describe, it, expect, vi, beforeEach } from 'vitest'
import axios from 'axios'
import api from '../api/axios'

// Mock axios
vi.mock('axios')
const mockedAxios = vi.mocked(axios)

describe('API Configuration', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  it('should have correct base configuration', () => {
    // Test that our API instance is properly configured
    expect(api.defaults.baseURL).toBeDefined()
    expect(api.defaults.withCredentials).toBe(true)
  })

  it('should make GET requests correctly', async () => {
    const mockData = { data: [{ id: 1, label: 'Test' }] }
    mockedAxios.get.mockResolvedValue(mockData)

    const result = await api.get('/api/expenses')

    expect(mockedAxios.get).toHaveBeenCalledWith('/api/expenses')
    expect(result).toEqual(mockData)
  })

  it('should make POST requests correctly', async () => {
    const mockResponse = { data: { message: 'Created' } }
    const requestData = { label: 'New Expense', amount: 10 }
    
    mockedAxios.post.mockResolvedValue(mockResponse)

    const result = await api.post('/api/expense', requestData)

    expect(mockedAxios.post).toHaveBeenCalledWith('/api/expense', requestData)
    expect(result).toEqual(mockResponse)
  })

  it('should make DELETE requests correctly', async () => {
    const mockResponse = { data: { message: 'Deleted' } }
    
    mockedAxios.delete.mockResolvedValue(mockResponse)

    const result = await api.delete('/api/expense/1')

    expect(mockedAxios.delete).toHaveBeenCalledWith('/api/expense/1')
    expect(result).toEqual(mockResponse)
  })

  it('should make PUT requests correctly', async () => {
    const mockResponse = { data: { message: 'Updated' } }
    const requestData = { label: 'Updated Expense', amount: 20 }
    
    mockedAxios.put.mockResolvedValue(mockResponse)

    const result = await api.put('/api/expense/1', requestData)

    expect(mockedAxios.put).toHaveBeenCalledWith('/api/expense/1', requestData)
    expect(result).toEqual(mockResponse)
  })

  it('should handle request errors', async () => {
    const errorMessage = 'Network Error'
    mockedAxios.get.mockRejectedValue(new Error(errorMessage))

    await expect(api.get('/api/expenses')).rejects.toThrow(errorMessage)
  })

  it('should handle HTTP error responses', async () => {
    const errorResponse = {
      response: {
        status: 404,
        data: { error: 'Not Found' }
      }
    }
    mockedAxios.get.mockRejectedValue(errorResponse)

    try {
      await api.get('/api/nonexistent')
    } catch (error) {
      expect(error.response.status).toBe(404)
      expect(error.response.data.error).toBe('Not Found')
    }
  })

  it('should handle authentication errors', async () => {
    const authError = {
      response: {
        status: 401,
        data: { error: 'Unauthorized' }
      }
    }
    mockedAxios.get.mockRejectedValue(authError)

    try {
      await api.get('/api/expenses')
    } catch (error) {
      expect(error.response.status).toBe(401)
      expect(error.response.data.error).toBe('Unauthorized')
    }
  })

  it('should pass withCredentials option', async () => {
    const mockData = { data: [] }
    mockedAxios.get.mockResolvedValue(mockData)

    await api.get('/api/expenses', { withCredentials: true })

    expect(mockedAxios.get).toHaveBeenCalledWith('/api/expenses', { withCredentials: true })
  })
})
