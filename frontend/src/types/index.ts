export interface User {
  id: number;
  name: string;
  email: string;
  role: 'super_admin' | 'store_owner' | 'customer';
  is_active: boolean;
  created_at: string;
  store?: Store;
}

export interface Store {
  id: number;
  uuid: string;
  name: string;
  slug: string;
  description?: string;
  logo?: string;
  primary_color: string;
  whatsapp?: string;
  address?: string;
  city?: string;
  state?: string;
  minimum_order: number;
  delivery_fee: number;
  free_delivery: boolean;
  is_active: boolean;
  is_suspended: boolean;
  plan?: Plan;
}

export interface Plan {
  id: number;
  name: string;
  description?: string;
  price: number;
  limit_products: number | null;
  limit_categories: number | null;
  has_domain: boolean;
  has_api: boolean;
  is_active: boolean;
}

export interface Category {
  id: number;
  store_id: number;
  name: string;
  slug: string;
  description?: string;
  image?: string;
  order: number;
  is_active: boolean;
  products_count?: number;
}

export interface Product {
  id: number;
  store_id: number;
  category_id?: number;
  category?: Category;
  name: string;
  slug: string;
  description?: string;
  price: number;
  discount_price?: number;
  discount_percent?: number;
  image?: string;
  images?: string[];
  is_active: boolean;
  is_featured: boolean;
  stock?: number;
  min_gramage: number;
  max_gramage: number;
  gramage_step: number;
  variations?: ProductVariation[];
  final_price?: number;
  has_discount?: boolean;
  gramages?: number[];
}

export interface ProductVariation {
  id: number;
  product_id: number;
  name: string;
  price_adjust: number;
  is_active: boolean;
}

export interface Kit {
  id: number;
  store_id: number;
  name: string;
  slug: string;
  description?: string;
  items_list?: string;
  price: number;
  price_per_person?: number;
  min_people: number;
  max_people?: number;
  estimated_weight?: number;
  image?: string;
  is_active: boolean;
  items?: KitItem[];
}

export interface KitItem {
  id: number;
  kit_id: number;
  product_id: number;
  product_name: string;
  quantity: number;
  unit: string;
}

export interface Order {
  id: number;
  store_id: number;
  order_number: string;
  customer_name: string;
  customer_phone: string;
  customer_email?: string;
  delivery_address?: string;
  delivery_number?: string;
  delivery_complement?: string;
  delivery_neighborhood?: string;
  delivery_city?: string;
  delivery_state?: string;
  delivery_reference?: string;
  subtotal: number;
  delivery_fee: number;
  discount: number;
  total: number;
  payment_method?: 'money' | 'pix' | 'card' | 'transfer';
  change_for?: number;
  payment_status: 'pending' | 'paid' | 'failed';
  status: 'pending' | 'confirmed' | 'preparing' | 'ready' | 'dispatched' | 'canceled';
  admin_notes?: string;
  cancellation_reason?: string;
  whatsapp_message?: string;
  created_at: string;
  items?: OrderItem[];
}

export interface OrderItem {
  id: number;
  order_id: number;
  product_id?: number;
  product_name: string;
  variation_name?: string;
  quantity: number;
  gramage?: number;
  unit_price: number;
  subtotal: number;
  observations?: string;
}

export interface Banner {
  id: number;
  store_id: number;
  image: string;
  link?: string;
  title?: string;
  description?: string;
  order: number;
  is_active: boolean;
}

export interface Neighborhood {
  id: number;
  store_id: number;
  name: string;
  delivery_fee: number;
  minimum_order?: number;
  is_active: boolean;
}

export interface CartItem {
  id: string;
  product: Product;
  variation?: ProductVariation;
  gramage: number;
  quantity: number;
  observations?: string;
  subtotal: number;
}

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  meta?: {
    current_page?: number;
    last_page?: number;
    per_page?: number;
    total?: number;
  };
}
