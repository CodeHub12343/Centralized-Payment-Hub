import axios, { AxiosError } from 'axios';

// API Configuration
const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api';

// Create axios instance
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  timeout: 30000, // 30 second timeout to prevent hanging requests
});

// Add request interceptor to include JWT token
apiClient.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => Promise.reject(error)
);

// Add response interceptor to handle token expiration
apiClient.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      // Token expired or invalid
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

// ============================================
// AUTH ENDPOINTS
// ============================================

export const authAPI = {
  login: async (username: string, password: string) => {
    const response = await apiClient.post('/auth/login', { username, password });
    return response.data;
  },

  logout: async () => {
    const response = await apiClient.post('/auth/logout', {});
    return response.data;
  },
};

// ============================================
// TRANSACTION ENDPOINTS
// ============================================

export interface TransactionFilters {
  search?: string;
  site?: string;
  status?: 'success' | 'pending' | 'failed';
  page?: number;
  per_page?: number;
}

export const transactionAPI = {
  getTransactions: async (filters: TransactionFilters = {}) => {
    const params = new URLSearchParams();
    if (filters.search) params.append('search', filters.search);
    if (filters.site) params.append('site', filters.site);
    if (filters.status) params.append('status', filters.status);
    params.append('page', (filters.page || 1).toString());
    params.append('per_page', (filters.per_page || 50).toString());

    const response = await apiClient.get(`/transactions?${params.toString()}`);
    return response.data;
  },
};

// ============================================
// WEBSITE ENDPOINTS
// ============================================

export interface Website {
  id?: number;
  site_code: string;
  secret_key: string;
  success_url: string;
  fail_url: string;
  is_active?: number;
  created_at?: string;
}

export const websiteAPI = {
  getWebsites: async () => {
    const response = await apiClient.get('/websites');
    return response.data;
  },

  createWebsite: async (website: Website) => {
    const response = await apiClient.post('/websites', website);
    return response.data;
  },

  updateWebsite: async (siteCode: string, updates: Partial<Website>) => {
    const response = await apiClient.put(`/websites/${siteCode}`, updates);
    return response.data;
  },

  deleteWebsite: async (siteCode: string) => {
    const response = await apiClient.delete(`/websites/${siteCode}`);
    return response.data;
  },
};

// ============================================
// DASHBOARD ENDPOINTS
// ============================================

export const dashboardAPI = {
  getMetrics: async () => {
    const response = await apiClient.get('/dashboard/metrics');
    return response.data;
  },

  getGatewayStatus: async () => {
    const response = await apiClient.get('/gateways/status');
    return response.data;
  },
};

export default apiClient;
