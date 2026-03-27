import { useEffect, useState } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, RefreshControl, Alert, Modal, TextInput, ScrollView } from 'react-native';
import { useRouter } from 'expo-router';
import api from '@/services/api';
import { Order } from '@/types';

const STATUS_COLORS: Record<string, string> = {
  pending: '#FFA500',
  confirmed: '#2196F3',
  preparing: '#9C27B0',
  ready: '#4CAF50',
  dispatched: '#4CAF50',
  canceled: '#F44336',
};

const STATUS_LABELS: Record<string, string> = {
  pending: 'Pendente',
  confirmed: 'Confirmado',
  preparing: 'Preparando',
  ready: 'Pronto',
  dispatched: 'Enviado',
  canceled: 'Cancelado',
};

export default function OrdersScreen() {
  const router = useRouter();
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);
  const [showModal, setShowModal] = useState(false);
  const [filter, setFilter] = useState<string | null>(null);

  const fetchOrders = async () => {
    try {
      const params: any = {};
      if (filter) params.status = filter;
      
      const response = await api.getClient().get('/stores/me/orders', { params });
      setOrders(response.data.data.data || response.data.data);
    } catch (error) {
      console.error('Error fetching orders:', error);
    }
  };

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      await fetchOrders();
      setLoading(false);
    };
    load();
  }, [filter]);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchOrders();
    setRefreshing(false);
  };

  const updateStatus = async (order: Order, newStatus: string) => {
    try {
      await api.getClient().put(`/stores/me/orders/${order.id}/status`, {
        status: newStatus,
      });
      fetchOrders();
      setShowModal(false);
    } catch (error) {
      Alert.alert('Erro', 'Não foi possível atualizar o status');
    }
  };

  const formatCurrency = (value: number) => {
    return 'R$ ' + value.toFixed(2).replace('.', ',');
  };

  const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString('pt-BR', {
      day: '2-digit',
      month: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const renderOrder = ({ item }: { item: Order }) => (
    <TouchableOpacity 
      style={styles.orderCard}
      onPress={() => {
        setSelectedOrder(item);
        setShowModal(true);
      }}
    >
      <View style={styles.orderHeader}>
        <Text style={styles.orderNumber}>{item.order_number}</Text>
        <View style={[styles.statusBadge, { backgroundColor: STATUS_COLORS[item.status] }]}>
          <Text style={styles.statusText}>{STATUS_LABELS[item.status]}</Text>
        </View>
      </View>
      
      <Text style={styles.customerName}>{item.customer_name}</Text>
      <Text style={styles.customerPhone}>{item.customer_phone}</Text>
      
      <View style={styles.orderFooter}>
        <Text style={styles.orderDate}>{formatDate(item.created_at)}</Text>
        <Text style={styles.orderTotal}>{formatCurrency(item.total)}</Text>
      </View>
      
      <Text style={styles.itemCount}>
        {item.items?.length || 0} item(s)
      </Text>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()}>
          <Text style={styles.backButton}>← Voltar</Text>
        </TouchableOpacity>
        <Text style={styles.title}>Pedidos</Text>
        <View style={{ width: 40 }} />
      </View>

      {/* Filters */}
      <FlatList
        horizontal
        data={[null, 'pending', 'confirmed', 'preparing', 'ready', 'dispatched', 'canceled']}
        keyExtractor={(item) => String(item || 'all')}
        renderItem={({ item }) => (
          <TouchableOpacity
            style={[
              styles.filterChip,
              filter === item && styles.filterChipActive
            ]}
            onPress={() => setFilter(item)}
          >
            <Text style={[
              styles.filterChipText,
              filter === item && styles.filterChipTextActive
            ]}>
              {item ? STATUS_LABELS[item] : 'Todos'}
            </Text>
          </TouchableOpacity>
        )}
        style={styles.filtersList}
        showsHorizontalScrollIndicator={false}
      />

      {/* Orders List */}
      <FlatList
        data={orders}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderOrder}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
        contentContainerStyle={styles.list}
        ListEmptyComponent={
          <View style={styles.empty}>
            <Text style={styles.emptyText}>Nenhum pedido encontrado</Text>
          </View>
        }
      />

      {/* Order Detail Modal */}
      <Modal visible={showModal} animationType="slide">
        <View style={styles.modalContainer}>
          <View style={styles.modalHeader}>
            <TouchableOpacity onPress={() => setShowModal(false)}>
              <Text style={styles.modalClose}>✕</Text>
            </TouchableOpacity>
            <Text style={styles.modalTitle}>Pedido {selectedOrder?.order_number}</Text>
            <View style={{ width: 40 }} />
          </View>

          <ScrollView style={styles.modalContent}>
            {/* Customer Info */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Cliente</Text>
              <Text style={styles.infoText}>{selectedOrder?.customer_name}</Text>
              <Text style={styles.infoText}>{selectedOrder?.customer_phone}</Text>
              {selectedOrder?.customer_email && (
                <Text style={styles.infoText}>{selectedOrder?.customer_email}</Text>
              )}
            </View>

            {/* Delivery Info */}
            {selectedOrder?.delivery_address && (
              <View style={styles.section}>
                <Text style={styles.sectionTitle}>Entrega</Text>
                <Text style={styles.infoText}>
                  {selectedOrder?.delivery_address}, {selectedOrder?.delivery_number}
                </Text>
                {selectedOrder?.delivery_neighborhood && (
                  <Text style={styles.infoText}>{selectedOrder?.delivery_neighborhood}</Text>
                )}
              </View>
            )}

            {/* Items */}
            <View style={styles.section}>
              <Text style={styles.sectionTitle}>Itens</Text>
              {selectedOrder?.items?.map((item, index) => (
                <View key={index} style={styles.itemRow}>
                  <Text style={styles.itemQuantity}>{item.quantity}x</Text>
                  <View style={styles.itemInfo}>
                    <Text style={styles.itemName}>{item.product_name}</Text>
                    {item.variation_name && (
                      <Text style={styles.itemVariation}>{item.variation_name}</Text>
                    )}
                    {item.gramage && (
                      <Text style={styles.itemGramage}>{item.gramage}g</Text>
                    )}
                  </View>
                  <Text style={styles.itemPrice}>
                    R$ {item.subtotal.toFixed(2).replace('.', ',')}
                  </Text>
                </View>
              ))}
            </View>

            {/* Totals */}
            <View style={styles.section}>
              <View style={styles.totalRow}>
                <Text>Subtotal</Text>
                <Text>{formatCurrency(selectedOrder?.subtotal || 0)}</Text>
              </View>
              <View style={styles.totalRow}>
                <Text>Entrega</Text>
                <Text>{formatCurrency(selectedOrder?.delivery_fee || 0)}</Text>
              </View>
              {selectedOrder?.discount > 0 && (
                <View style={styles.totalRow}>
                  <Text style={{ color: '#4CAF50' }}>Desconto</Text>
                  <Text style={{ color: '#4CAF50' }}>- {formatCurrency(selectedOrder?.discount || 0)}</Text>
                </View>
              )}
              <View style={[styles.totalRow, styles.totalFinal]}>
                <Text style={{ fontWeight: 'bold' }}>Total</Text>
                <Text style={{ fontWeight: 'bold', color: '#FF4500' }}>
                  {formatCurrency(selectedOrder?.total || 0)}
                </Text>
              </View>
            </View>

            {/* Actions */}
            <View style={styles.actions}>
              {selectedOrder?.status === 'pending' && (
                <TouchableOpacity
                  style={[styles.actionButton, { backgroundColor: '#2196F3' }]}
                  onPress={() => updateStatus(selectedOrder, 'confirmed')}
                >
                  <Text style={styles.actionButtonText}>Confirmar Pedido</Text>
                </TouchableOpacity>
              )}

              {selectedOrder?.status === 'confirmed' && (
                <TouchableOpacity
                  style={[styles.actionButton, { backgroundColor: '#9C27B0' }]}
                  onPress={() => updateStatus(selectedOrder, 'preparing')}
                >
                  <Text style={styles.actionButtonText}>Iniciar Preparo</Text>
                </TouchableOpacity>
              )}

              {selectedOrder?.status === 'preparing' && (
                <TouchableOpacity
                  style={[styles.actionButton, { backgroundColor: '#4CAF50' }]}
                  onPress={() => updateStatus(selectedOrder, 'ready')}
                >
                  <Text style={styles.actionButtonText}>Marcar como Pronto</Text>
                </TouchableOpacity>
              )}

              {selectedOrder?.status === 'ready' && (
                <TouchableOpacity
                  style={[styles.actionButton, { backgroundColor: '#4CAF50' }]}
                  onPress={() => updateStatus(selectedOrder, 'dispatched')}
                >
                  <Text style={styles.actionButtonText}>Marcar como Entregue</Text>
                </TouchableOpacity>
              )}

              {selectedOrder?.canBeCanceled() && (
                <TouchableOpacity
                  style={[styles.actionButton, { backgroundColor: '#F44336' }]}
                  onPress={() => updateStatus(selectedOrder, 'canceled')}
                >
                  <Text style={styles.actionButtonText}>Cancelar Pedido</Text>
                </TouchableOpacity>
              )}
            </View>
          </ScrollView>
        </View>
      </Modal>
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
    backgroundColor: '#fff',
  },
  backButton: {
    fontSize: 16,
    color: '#FF4500',
  },
  title: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
  },
  filtersList: {
    paddingHorizontal: 16,
    paddingVertical: 12,
    backgroundColor: '#fff',
  },
  filterChip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: '#f5f5f5',
    marginRight: 8,
  },
  filterChipActive: {
    backgroundColor: '#FF4500',
  },
  filterChipText: {
    fontSize: 12,
    color: '#666',
  },
  filterChipTextActive: {
    color: '#fff',
    fontWeight: '600',
  },
  list: {
    padding: 16,
  },
  orderCard: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
  },
  orderHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  orderNumber: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
  },
  statusBadge: {
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 12,
  },
  statusText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '600',
  },
  customerName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
  },
  customerPhone: {
    fontSize: 12,
    color: '#666',
    marginTop: 2,
  },
  orderFooter: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 8,
  },
  orderDate: {
    fontSize: 12,
    color: '#666',
  },
  orderTotal: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FF4500',
  },
  itemCount: {
    fontSize: 12,
    color: '#666',
    marginTop: 4,
  },
  empty: {
    alignItems: 'center',
    padding: 40,
  },
  emptyText: {
    fontSize: 16,
    color: '#666',
  },
  modalContainer: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  modalHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: 16,
    backgroundColor: '#fff',
  },
  modalClose: {
    fontSize: 24,
    color: '#666',
  },
  modalTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
  },
  modalContent: {
    flex: 1,
    padding: 16,
  },
  section: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 8,
  },
  infoText: {
    fontSize: 14,
    color: '#666',
    marginBottom: 4,
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 8,
    borderBottomWidth: 1,
    borderBottomColor: '#f0f0f0',
  },
  itemQuantity: {
    fontSize: 14,
    fontWeight: 'bold',
    color: '#333',
    width: 40,
  },
  itemInfo: {
    flex: 1,
  },
  itemName: {
    fontSize: 14,
    color: '#333',
  },
  itemVariation: {
    fontSize: 12,
    color: '#666',
  },
  itemGramage: {
    fontSize: 12,
    color: '#666',
  },
  itemPrice: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
  },
  totalRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 4,
  },
  totalFinal: {
    borderTopWidth: 1,
    borderTopColor: '#eee',
    marginTop: 8,
    paddingTop: 8,
  },
  actions: {
    marginTop: 16,
    marginBottom: 32,
  },
  actionButton: {
    padding: 16,
    borderRadius: 12,
    alignItems: 'center',
    marginBottom: 12,
  },
  actionButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
});
