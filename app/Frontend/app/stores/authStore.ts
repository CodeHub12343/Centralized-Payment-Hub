import { create } from 'zustand';
import { authAPI } from '../api/client';

export interface AuthUser {
  id: number;
  username: string;
  email: string;
}

interface AuthStore {
  token: string | null;
  user: AuthUser | null;
  isLoading: boolean;
  error: string | null;
  isAuthenticated: boolean;

  // Actions
  login: (username: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  initializeAuth: () => void;
  clearError: () => void;
}

export const useAuthStore = create<AuthStore>((set) => ({
  token: null,
  user: null,
  isLoading: false,
  error: null,
  isAuthenticated: false,

  login: async (username: string, password: string) => {
    set({ isLoading: true, error: null });
    try {
      const response = await authAPI.login(username, password);
      
      console.log('[Auth Store] Login response:', response);
      console.log('[Auth Store] Token:', response.token);
      console.log('[Auth Store] Admin:', response.admin);

      if (response.success && response.token) {
        // Store token and user
        localStorage.setItem('auth_token', response.token);
        localStorage.setItem('auth_user', JSON.stringify(response.admin));
        
        console.log('[Auth Store] Token stored to localStorage:', localStorage.getItem('auth_token'));

        set({
          token: response.token,
          user: response.admin,
          isAuthenticated: true,
          isLoading: false,
          error: null,
        });
        console.log('[Auth Store] State updated - user is authenticated');
      } else {
        console.log('[Auth Store] Login failed - no success flag or token');
        set({
          isLoading: false,
          error: response.error || 'Login failed',
        });
      }
    } catch (err: any) {
      const errorMessage = err.response?.data?.error || err.message || 'Login failed';
      console.error('[Auth Store] Login error:', err);
      set({
        isLoading: false,
        error: errorMessage,
      });
      throw err;
    }
  },

  logout: async () => {
    set({ isLoading: true });
    try {
      await authAPI.logout();
    } catch (err) {
      console.error('Logout error:', err);
    } finally {
      // Clear storage regardless of API response
      localStorage.removeItem('auth_token');
      localStorage.removeItem('auth_user');

      set({
        token: null,
        user: null,
        isAuthenticated: false,
        isLoading: false,
        error: null,
      });
    }
  },

  initializeAuth: () => {
    const token = localStorage.getItem('auth_token');
    const userStr = localStorage.getItem('auth_user');

    if (token && userStr) {
      try {
        const user = JSON.parse(userStr);
        set({
          token,
          user,
          isAuthenticated: true,
        });
      } catch (err) {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
        set({ isAuthenticated: false });
      }
    }
  },

  clearError: () => set({ error: null }),
}));
