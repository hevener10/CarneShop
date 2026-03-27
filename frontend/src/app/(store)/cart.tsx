import { useState } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, TextInput, Alert, Image } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import * as Linking from 'expo-linking';
import api from '@/services/api';
import { useCartStore } from '@/stores/cartStore';

/**
 * Exibe o carrinho atual e dispara o checkout publico da loja.
 */
export default function CartScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const storeSlug = params.store as string || 'demo';
  
  const { items, subtotal, removeItem, updateQuantity, updateGramage, clearCart } = useCartStore();
  const [loading, setLoading] = useState(false);

  /**
   * Formata valores monetarios exibidos no resumo do carrinho.
   */
  const formatPrice = (value: number) => {
    return 'R$ ' + value.toFixed(2).replace('.', ',');
  };

  /**
   * Envia o pedido ao backend e abre o link de WhatsApp retornado no checkout.
   */
  const handleCheckout = async () => {
    if (items.length === 0) {
      Alert.alert('Erro', 'Seu carrinho está vazio');
      return;
    }

    setLoading(true);
    try {
      // Prepare order data
      const orderData = {
        customer_name: 'Cliente',
        customer_phone: '11999999999',
        items: items.map(item => ({
          product_id: item.product.id,
          variation_id: item.variation?.id,
          quantity: item.quantity,
          gramage: item.gramage,
          observations: item.observations,
        })),
        payment_method: 'money',
      };

      const response = await api.getClient().post(`/public/stores/${storeSlug}/checkout`, orderData);
      
      const { whatsapp_link } = response.data.data;
      
      Alert.alert(
        'Pedido Realizado! 🎉',
        'Seu pedido foi enviado com sucesso. Você será redirecionado para o WhatsApp.',
        [
          { text: 'OK', onPress: () => {
            clearCart();
            router.back();
          }}
        ]
      );
      
      // Open WhatsApp if available
      if (whatsapp_link) {
        Linking.openURL(whatsapp_link);
      }
    } catch (error: any) {
      const message = error.response?.data?.message || 'Erro ao fazer pedido';
      Alert.alert('Erro', message);
    } finally {
      setLoading(false);
    }
  };

  /**
   * Renderiza uma linha do carrinho com controles de quantidade.
   */
  const renderItem = ({ item }: any) => (
    <View style={styles.cartItem}>
      <View style={styles.itemImage}>
        {item.product.image ? (
          <Image source={{ uri: item.product.image }} style={styles.productImg} />
        ) : (
          <View style={styles.productPlaceholder}><Text>🥩</Text></View>
        )}
      </View>
      <View style={styles.itemInfo}>
        <Text style={styles.itemName} numberOfLines={2}>{item.product.name}</Text>
        {item.variation && (
          <Text style={styles.itemVariation}>{item.variation.name}</Text>
        )}
        <Text style={styles.itemGramage}>{item.gramage}g</Text>
        
        <View style={styles.itemActions}>
          <View style={styles.quantityControls}>
            <TouchableOpacity 
              style={styles.quantityButton}
              onPress={() => updateQuantity(item.id, item.quantity - 1)}
            >
              <Text style={styles.quantityButtonText}>-</Text>
            </TouchableOpacity>
            <Text style={styles.quantity}>{item.quantity}</Text>
            <TouchableOpacity 
              style={styles.quantityButton}
              onPress={() => updateQuantity(item.id, item.quantity + 1)}
            >
              <Text style={styles.quantityButtonText}>+</Text>
            </TouchableOpacity>
          </View>
          
          <Text style={styles.itemSubtotal}>{formatPrice(item.subtotal)}</Text>
        </View>
      </View>
      <TouchableOpacity 
        style={styles.removeButton}
        onPress={() => removeItem(item.id)}
      >
        <Text style={styles.removeButtonText}>✕</Text>
      </TouchableOpacity>
    </View>
  );

  if (items.length === 0) {
    return (
      <View style={styles.container}>
        <View style={styles.header}>
          <TouchableOpacity onPress={() => router.back()}>
            <Text style={styles.backButton}>←</Text>
          </TouchableOpacity>
          <Text style={styles.title}>Carrinho</Text>
          <View style={{ width: 24 }} />
        </View>
        <View style={styles.emptyCart}>
          <Text style={styles.emptyIcon}>🛒</Text>
          <Text style={styles.emptyTitle}>Carrinho vazio</Text>
          <Text style={styles.emptyText}>Adicione produtos para fazer seu pedido</Text>
          <TouchableOpacity 
            style={styles.continueButton}
            onPress={() => router.back()}
          >
            <Text style={styles.continueButtonText}>Ver Produtos</Text>
          </TouchableOpacity>
        </View>
      </View>
    );
  }

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()}>
          <Text style={styles.backButton}>←</Text>
        </TouchableOpacity>
        <Text style={styles.title}>Carrinho ({items.length})</Text>
        <TouchableOpacity onPress={() => {
          Alert.alert('Limpar carrinho', 'Tem certeza?', [
            { text: 'Cancelar', style: 'cancel' },
            { text: 'Limpar', style: 'destructive', onPress: clearCart },
          ]);
        }}>
          <Text style={styles.clearButton}>Limpar</Text>
        </TouchableOpacity>
      </View>

      {/* Items */}
      <FlatList
        data={items}
        keyExtractor={(item) => item.id}
        renderItem={renderItem}
        contentContainerStyle={styles.list}
      />

      {/* Summary */}
      <View style={styles.summary}>
        <View style={styles.summaryRow}>
          <Text style={styles.summaryLabel}>Subtotal</Text>
          <Text style={styles.summaryValue}>{formatPrice(subtotal)}</Text>
        </View>
        <View style={styles.summaryRow}>
          <Text style={styles.summaryLabel}>Entrega</Text>
          <Text style={styles.summaryValue}>A calcular</Text>
        </View>
        <View style={[styles.summaryRow, styles.summaryTotal]}>
          <Text style={styles.totalLabel}>Total</Text>
          <Text style={styles.totalValue}>{formatPrice(subtotal)}</Text>
        </View>

        <TouchableOpacity 
          style={[styles.checkoutButton, loading && styles.checkoutButtonDisabled]}
          onPress={handleCheckout}
          disabled={loading}
        >
          <Text style={styles.checkoutButtonText}>
            {loading ? 'Enviando...' : 'Finalizar Pedido'}
          </Text>
        </TouchableOpacity>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    backgroundColor: '#FF4500',
  },
  backButton: {
    fontSize: 24,
    color: '#fff',
  },
  title: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#fff',
  },
  clearButton: {
    color: '#fff',
    fontSize: 14,
  },
  list: {
    padding: 16,
  },
  cartItem: {
    flexDirection: 'row',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 12,
    marginBottom: 12,
  },
  itemImage: {
    width: 80,
    height: 80,
    borderRadius: 8,
    backgroundColor: '#f5f5f5',
    overflow: 'hidden',
  },
  productImg: {
    width: '100%',
    height: '100%',
  },
  productPlaceholder: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  itemInfo: {
    flex: 1,
    marginLeft: 12,
  },
  itemName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
  },
  itemVariation: {
    fontSize: 12,
    color: '#666',
    marginTop: 2,
  },
  itemGramage: {
    fontSize: 12,
    color: '#999',
    marginTop: 2,
  },
  itemActions: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 8,
  },
  quantityControls: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  quantityButton: {
    width: 28,
    height: 28,
    borderRadius: 14,
    backgroundColor: '#f5f5f5',
    alignItems: 'center',
    justifyContent: 'center',
  },
  quantityButtonText: {
    fontSize: 18,
    color: '#333',
  },
  quantity: {
    fontSize: 16,
    fontWeight: '600',
    marginHorizontal: 12,
  },
  itemSubtotal: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FF4500',
  },
  removeButton: {
    padding: 4,
  },
  removeButtonText: {
    fontSize: 16,
    color: '#999',
  },
  summary: {
    backgroundColor: '#fff',
    padding: 16,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
  },
  summaryRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 8,
  },
  summaryLabel: {
    fontSize: 14,
    color: '#666',
  },
  summaryValue: {
    fontSize: 14,
    color: '#333',
  },
  summaryTotal: {
    borderTopWidth: 1,
    borderTopColor: '#eee',
    marginTop: 8,
    paddingTop: 16,
  },
  totalLabel: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
  },
  totalValue: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#FF4500',
  },
  checkoutButton: {
    backgroundColor: '#FF4500',
    padding: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginTop: 16,
  },
  checkoutButtonDisabled: {
    opacity: 0.6,
  },
  checkoutButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  emptyCart: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
  },
  emptyIcon: {
    fontSize: 64,
    marginBottom: 16,
  },
  emptyTitle: {
    fontSize: 20,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 8,
  },
  emptyText: {
    fontSize: 14,
    color: '#666',
    textAlign: 'center',
  },
  continueButton: {
    marginTop: 24,
    backgroundColor: '#FF4500',
    paddingHorizontal: 32,
    paddingVertical: 12,
    borderRadius: 24,
  },
  continueButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
});
