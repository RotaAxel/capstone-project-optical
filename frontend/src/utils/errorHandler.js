// src/utils/errorHandler.js
// Centralized Error Handling - ISO 25010 Reliability & Maintainability

export class AppError extends Error {
  constructor(message, code = 'UNKNOWN_ERROR', statusCode = 500) {
    super(message)
    this.code = code
    this.statusCode = statusCode
    this.timestamp = new Date().toISOString()
  }
}

export const ErrorTypes = {
  NETWORK_ERROR: 'NETWORK_ERROR',
  VALIDATION_ERROR: 'VALIDATION_ERROR',
  AUTHENTICATION_ERROR: 'AUTHENTICATION_ERROR',
  AUTHORIZATION_ERROR: 'AUTHORIZATION_ERROR',
  NOT_FOUND: 'NOT_FOUND',
  CONFLICT: 'CONFLICT',
  SERVER_ERROR: 'SERVER_ERROR',
  TIMEOUT: 'TIMEOUT',
  UNKNOWN: 'UNKNOWN_ERROR'
}

export const ErrorMessages = {
  NETWORK_ERROR: 'Network connection failed. Please check your internet connection.',
  VALIDATION_ERROR: 'Please check your input and try again.',
  AUTHENTICATION_ERROR: 'Authentication failed. Please log in again.',
  AUTHORIZATION_ERROR: 'You do not have permission to perform this action.',
  NOT_FOUND: 'The requested resource was not found.',
  CONFLICT: 'This action conflicts with existing data.',
  SERVER_ERROR: 'Server error. Please try again later.',
  TIMEOUT: 'Request timeout. Please try again.',
  UNKNOWN: 'An unexpected error occurred.'
}

// Map HTTP status codes to error types
export const getErrorType = (statusCode) => {
  const errorMap = {
    400: ErrorTypes.VALIDATION_ERROR,
    401: ErrorTypes.AUTHENTICATION_ERROR,
    403: ErrorTypes.AUTHORIZATION_ERROR,
    404: ErrorTypes.NOT_FOUND,
    409: ErrorTypes.CONFLICT,
    500: ErrorTypes.SERVER_ERROR,
    503: ErrorTypes.SERVER_ERROR,
    0: ErrorTypes.NETWORK_ERROR
  }
  return errorMap[statusCode] || ErrorTypes.UNKNOWN
}

export const handleError = (error) => {
  // Network error
  if (!window.navigator.onLine) {
    return {
      type: ErrorTypes.NETWORK_ERROR,
      message: ErrorMessages.NETWORK_ERROR,
      severity: 'error'
    }
  }

  // Fetch/Network error
  if (error instanceof TypeError && error.message === 'Failed to fetch') {
    return {
      type: ErrorTypes.NETWORK_ERROR,
      message: ErrorMessages.NETWORK_ERROR,
      severity: 'error'
    }
  }

  // Timeout error
  if (error.code === 'ECONNABORTED') {
    return {
      type: ErrorTypes.TIMEOUT,
      message: ErrorMessages.TIMEOUT,
      severity: 'warning'
    }
  }

  // Custom app error
  if (error instanceof AppError) {
    return {
      type: error.code,
      message: error.message,
      severity: error.statusCode >= 500 ? 'error' : 'warning'
    }
  }

  // Generic error
  return {
    type: ErrorTypes.UNKNOWN,
    message: ErrorMessages.UNKNOWN,
    severity: 'error',
    details: error.message
  }
}

// Retry logic with exponential backoff
export const retryWithBackoff = async (fn, options = {}) => {
  const { maxRetries = 3, initialDelay = 1000, maxDelay = 10000 } = options
  let lastError

  for (let i = 0; i < maxRetries; i++) {
    try {
      return await fn()
    } catch (error) {
      lastError = error
      const delay = Math.min(initialDelay * Math.pow(2, i), maxDelay)
      
      if (i < maxRetries - 1) {
        await new Promise(resolve => setTimeout(resolve, delay))
      }
    }
  }

  throw lastError
}

// Debounce function for search/filter operations
export const debounce = (func, wait) => {
  let timeout
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout)
      func(...args)
    }
    clearTimeout(timeout)
    timeout = setTimeout(later, wait)
  }
}

// Log errors for debugging (implement with your logging service)
export const logError = (error, context = {}) => {
  const errorLog = {
    timestamp: new Date().toISOString(),
    message: error.message,
    code: error.code || 'UNKNOWN',
    context,
    userAgent: navigator.userAgent,
    url: window.location.href
  }
  
  // TODO: Send to error logging service (Sentry, LogRocket, etc.)
  return errorLog
}