// API Configuration
export const API_CONFIG = {
  baseUrl: process.env.EXPO_PUBLIC_API_URL || 'http://localhost:8000/api/v1',
  timeout: 30000,
};

// Endpoints
export const ENDPOINTS = {
  // Auth
  login: '/auth/login',
  register: '/auth/register',
  logout: '/auth/logout',
  me: '/auth/me',
  
  // Admin
  admin: {
    stores: '/admin/stores',
    plans: '/admin/plans',
  },
  
  // Store
  store: {
    me: '/stores/me',
    config: '/stores/me/config',
    theme: '/stores/me/theme',
  },
  
  // Categories
  categories: '/stores/{storeId}/categories',
  
  // Products
  products: '/stores/{storeId}/products',
  
  // Kits
  kits: '/stores/{storeId}/kits',
  
  // Orders
  orders: '/stores/{storeId}/orders',
  
  // Public
  public: {
    store: (subdomain: string) => `/public/stores/${subdomain}`,
    categories: (subdomain: string) => `/public/stores/${subdomain}/categories`,
    products: (subdomain: string) => `/public/stores/${subdomain}/products`,
    checkout: (subdomain: string) => `/public/stores/${subdomain}/checkout`,
  },
};
