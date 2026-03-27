import { create } from 'zustand';
import { persist, createJSONStorage } from 'zustand/middleware';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { Product, ProductVariation, CartItem } from '../types';

interface CartState {
  items: CartItem[];
  storeId: number | null;
  storeSlug: string | null;
  
  // Computed
  itemCount: number;
  subtotal: number;
  
  // Actions
  addItem: (product: Product, variation?: ProductVariation, gramage?: number, observations?: string) => void;
  removeItem: (itemId: string) => void;
  updateQuantity: (itemId: string, quantity: number) => void;
  updateGramage: (itemId: string, gramage: number) => void;
  updateObservations: (itemId: string, observations: string) => void;
  clearCart: () => void;
  setStore: (storeId: number, storeSlug: string) => void;
}

const generateItemId = (
  productId: number, 
  variationId?: number, 
  gramage?: number
): string => {
  return `${productId}-${variationId || 'none'}-${gramage || 'none'}`;
};

const calculateSubtotal = (items: CartItem[]): number => {
  return items.reduce((sum, item) => sum + item.subtotal, 0);
};

const calculateItemCount = (items: CartItem[]): number => {
  return items.reduce((sum, item) => sum + item.quantity, 0);
};

export const useCartStore = create<CartState>()(
  persist(
    (set, get) => ({
      items: [],
      storeId: null,
      storeSlug: null,
      itemCount: 0,
      subtotal: 0,

      addItem: (product, variation, gramage = product.min_gramage || 500, observations = '') => {
        const itemId = generateItemId(product.id, variation?.id, gramage);
        
        const existingItem = get().items.find(item => item.id === itemId);
        
        if (existingItem) {
          // Update quantity instead
          const newQuantity = existingItem.quantity + 1;
          const newSubtotal = calculateItemSubtotal(product, gramage, newQuantity, variation);
          
          set(state => {
            const newItems = state.items.map(item =>
              item.id === itemId 
                ? { ...item, quantity: newQuantity, subtotal: newSubtotal }
                : item
            );

            return {
              items: newItems,
              itemCount: calculateItemCount(newItems),
              subtotal: calculateSubtotal(newItems),
            };
          });
        } else {
          // Calculate price based on gramage
          const unitPrice = product.discount_price || product.price;
          const pricePerGram = unitPrice / 1000;
          const subtotal = pricePerGram * gramage;
          
          const newItem: CartItem = {
            id: itemId,
            product,
            variation,
            gramage,
            quantity: 1,
            observations,
            subtotal,
          };
          
          set(state => ({
            items: [...state.items, newItem],
            itemCount: calculateItemCount([...state.items, newItem]),
            subtotal: calculateSubtotal([...state.items, newItem]),
          }));
        }
      },

      removeItem: (itemId: string) => {
        set(state => {
          const newItems = state.items.filter(item => item.id !== itemId);
          return {
            items: newItems,
            itemCount: calculateItemCount(newItems),
            subtotal: calculateSubtotal(newItems),
          };
        });
      },

      updateQuantity: (itemId: string, quantity: number) => {
        if (quantity < 1) {
          get().removeItem(itemId);
          return;
        }
        
        set(state => {
          const newItems = state.items.map(item => {
            if (item.id === itemId) {
              const newSubtotal = calculateItemSubtotal(
                item.product, 
                item.gramage, 
                quantity,
                item.variation
              );
              return { ...item, quantity, subtotal: newSubtotal };
            }
            return item;
          });
          
          return {
            items: newItems,
            itemCount: calculateItemCount(newItems),
            subtotal: calculateSubtotal(newItems),
          };
        });
      },

      updateGramage: (itemId: string, gramage: number) => {
        set(state => {
          const newItems = state.items.map(item => {
            if (item.id === itemId) {
              const newSubtotal = calculateItemSubtotal(
                item.product, 
                gramage, 
                item.quantity,
                item.variation
              );
              return { ...item, gramage, subtotal: newSubtotal };
            }
            return item;
          });
          
          return {
            items: newItems,
            itemCount: calculateItemCount(newItems),
            subtotal: calculateSubtotal(newItems),
          };
        });
      },

      updateObservations: (itemId: string, observations: string) => {
        set(state => ({
          items: state.items.map(item => 
            item.id === itemId ? { ...item, observations } : item
          ),
        }));
      },

      clearCart: () => {
        set({
          items: [],
          itemCount: 0,
          subtotal: 0,
        });
      },

      setStore: (storeId: number, storeSlug: string) => {
        // Clear cart if changing stores
        const currentStoreId = get().storeId;
        if (currentStoreId && currentStoreId !== storeId) {
          get().clearCart();
        }
        set({ storeId, storeSlug });
      },
    }),
    {
      name: 'cart-storage',
      storage: createJSONStorage(() => AsyncStorage),
    }
  )
);

// Helper function
function calculateItemSubtotal(
  product: Product, 
  gramage: number, 
  quantity: number,
  variation?: ProductVariation
): number {
  const basePrice = product.discount_price || product.price;
  const variationAdjust = variation?.price_adjust || 0;
  const unitPrice = basePrice + variationAdjust;
  const pricePerGram = unitPrice / 1000;
  return pricePerGram * gramage * quantity;
}
