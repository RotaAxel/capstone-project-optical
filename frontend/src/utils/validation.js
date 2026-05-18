// src/utils/validation.js
// Input Validation & Sanitization - ISO 25010 Reliability & Security

export const ValidationRules = {
  // Email validation
  isValidEmail: (email) => {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return re.test(String(email).toLowerCase())
  },

  // Phone validation (Philippine format)
  isValidPhone: (phone) => {
    const re = /^(\+63|0)?9\d{9}$/
    return re.test(String(phone).replace(/\D/g, ''))
  },

  // Required field check
  isRequired: (value) => {
    return value !== null && value !== undefined && String(value).trim().length > 0
  },

  // Min length validation
  minLength: (value, length) => {
    return String(value).length >= length
  },

  // Max length validation
  maxLength: (value, length) => {
    return String(value).length <= length
  },

  // Number validation
  isNumber: (value) => {
    return !isNaN(parseFloat(value)) && isFinite(value)
  },

  // Currency validation
  isValidCurrency: (value) => {
    const re = /^\d+(\.\d{1,2})?$/
    return re.test(String(value))
  },

  // Date validation
  isValidDate: (dateString) => {
    const date = new Date(dateString)
    return date instanceof Date && !isNaN(date)
  },

  // Age validation (for patients)
  isValidAge: (dob) => {
    const today = new Date()
    const birthDate = new Date(dob)
    let age = today.getFullYear() - birthDate.getFullYear()
    const monthDiff = today.getMonth() - birthDate.getMonth()
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
      age--
    }
    return age >= 0 && age <= 120
  },

  // Password strength validation
  isStrongPassword: (password) => {
    const re = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/
    return re.test(password)
  },

  // Prescription value validation
  isValidRx: (value) => {
    const num = parseFloat(value)
    return !isNaN(num) && num >= -20 && num <= 20
  },

  // Stock quantity validation
  isValidStock: (value) => {
    const num = parseInt(value)
    return !isNaN(num) && num >= 0 && num <= 9999999
  }
}

export const SanitizeInput = {
  // XSS Prevention - remove dangerous characters
  sanitizeText: (text) => {
    if (!text) return ''
    return String(text)
      .replace(/[<>]/g, '')
      .trim()
  },

  // Sanitize email
  sanitizeEmail: (email) => {
    return String(email).toLowerCase().trim()
  },

  // Sanitize phone
  sanitizePhone: (phone) => {
    return String(phone).replace(/\D/g, '')
  },

  // Sanitize currency (remove non-numeric)
  sanitizeCurrency: (value) => {
    return String(value).replace(/[^\d.]/g, '')
  }
}

// Form validation engine
export const ValidateForm = (formData, rules) => {
  const errors = {}
  
  Object.keys(rules).forEach(field => {
    const fieldRules = rules[field]
    const value = formData[field]

    if (fieldRules.required && !ValidationRules.isRequired(value)) {
      errors[field] = `${field} is required`
      return
    }

    if (fieldRules.email && value && !ValidationRules.isValidEmail(value)) {
      errors[field] = 'Invalid email address'
    }

    if (fieldRules.phone && value && !ValidationRules.isValidPhone(value)) {
      errors[field] = 'Invalid phone number'
    }

    if (fieldRules.minLength && value && !ValidationRules.minLength(value, fieldRules.minLength)) {
      errors[field] = `Minimum ${fieldRules.minLength} characters required`
    }

    if (fieldRules.maxLength && value && !ValidationRules.maxLength(value, fieldRules.maxLength)) {
      errors[field] = `Maximum ${fieldRules.maxLength} characters allowed`
    }

    if (fieldRules.custom && value && !fieldRules.custom(value)) {
      errors[field] = fieldRules.customMessage || 'Invalid value'
    }
  })

  return {
    isValid: Object.keys(errors).length === 0,
    errors
  }
}