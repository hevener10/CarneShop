import { create } from 'zustand';
import * as SecureStore from 'expo-secure-store';
import api from '../services/api';
import { User } from '../types';

interface AuthState {
  user: User | null;
  token: string | null;
  isLoading: boolean;
  isAuthenticated: boolean;
  
  // Actions
  login: (email: string, password: string) => Promise<void>;
  register: (data: { name: string; email: string; password: string }) => Promise<void>;
  logout: () => Promise<void>;
  checkAuth: () => Promise<void>;
  updateUser: (user: User) => void;
}

export const useAuthStore = create<AuthState>((set, get) => ({
  user: null,
  token: null,
  isLoading: true,
  isAuthenticated: false,

  login: async (email: string, password: string) => {
    try {
      const response = await api.login(email, password);
      
      set({
        user: response.data.user,
        token: response.data.token,
        isAuthenticated: true,
      });
    } catch (error) {
      throw error;
    }
  },

  register: async (data: { name: string; email: string; password: string }) => {
    try {
      const response = await api.register(data);
      
      set({
        user: response.data.user,
        token: response.data.token,
        isAuthenticated: true,
      });
    } catch (error) {
      throw error;
    }
  },

  logout: async () => {
    try {
      await api.logout();
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      set({
        user: null,
        token: null,
        isAuthenticated: false,
      });
    }
  },

  checkAuth: async () => {
    try {
      const token = await SecureStore.getItemAsync('auth_token');
      
      if (!token) {
        set({ isLoading: false, isAuthenticated: false });
        return;
      }

      set({ token });
      
      const response = await api.getMe();
      set({
        user: response.data.data,
        isAuthenticated: true,
        isLoading: false,
      });
    } catch (error) {
      set({
        user: null,
        token: null,
        isAuthenticated: false,
        isLoading: false,
      });
    }
  },

  updateUser: (user: User) => {
    set({ user });
  },
}));
