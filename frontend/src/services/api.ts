import axios, { AxiosInstance, AxiosError, InternalAxiosRequestConfig } from 'axios';
import * as SecureStore from 'expo-secure-store';
import { API_CONFIG, ENDPOINTS } from '../config/api';

class ApiService {
  private client: AxiosInstance;
  private static instance: ApiService;

  private constructor() {
    this.client = axios.create({
      baseURL: API_CONFIG.baseUrl,
      timeout: API_CONFIG.timeout,
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
    });

    // Request interceptor
    this.client.interceptors.request.use(
      async (config: InternalAxiosRequestConfig) => {
        try {
          const token = await SecureStore.getItemAsync('auth_token');
          if (token) {
            config.headers.Authorization = `Bearer ${token}`;
          }
        } catch (error) {
          console.error('Error getting token:', error);
        }
        return config;
      },
      (error) => Promise.reject(error)
    );

    // Response interceptor
    this.client.interceptors.response.use(
      (response) => response,
      (error: AxiosError) => {
        if (error.response?.status === 401) {
          // Handle unauthorized
          this.logout();
        }
        return Promise.reject(error);
      }
    );
  }

  public static getInstance(): ApiService {
    if (!ApiService.instance) {
      ApiService.instance = new ApiService();
    }
    return ApiService.instance;
  }

  public getClient(): AxiosInstance {
    return this.client;
  }

  // Auth methods
  public async login(email: string, password: string) {
    const response = await this.client.post(ENDPOINTS.login, { email, password });
    if (response.data.token) {
      await SecureStore.setItemAsync('auth_token', response.data.token);
    }
    return response.data;
  }

  public async register(data: { name: string; email: string; password: string; password_confirmation: string }) {
    const response = await this.client.post(ENDPOINTS.register, data);
    if (response.data.token) {
      await SecureStore.setItemAsync('auth_token', response.data.token);
    }
    return response.data;
  }

  public async logout() {
    try {
      await this.client.post(ENDPOINTS.logout);
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      await SecureStore.deleteItemAsync('auth_token');
    }
  }

  public async getMe() {
    return this.client.get(ENDPOINTS.me);
  }

  // Helper to build URL with params
  public buildUrl(endpoint: string, params: Record<string, string> = {}): string {
    let url = endpoint;
    Object.entries(params).forEach(([key, value]) => {
      url = url.replace(`{${key}}`, value);
    });
    return url;
  }
}

export const api = ApiService.getInstance();
export default api;
