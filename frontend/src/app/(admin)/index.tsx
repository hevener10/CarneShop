import { useEffect, useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, ScrollView, RefreshControl } from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '../src/stores/authStore';
import api from '../src/services/api';

interface Stats {
  today: { orders: number; revenue: number };
  week: { orders: number; revenue: number };
  month: { orders: number; revenue: number };
  pending: number;
}

export default function AdminDashboard() {
  const router = useRouter();
  const { user } = useAuthStore();
  const [stats, setStats] = useState<Stats | null>(null);
  const [refreshing, setRefreshing] = useState(false);

  const fetchStats = async () => {
    try {
      const response = await api.getClient().get('/stores/me/stats');
      setStats(response.data.data);
    } catch (error) {
      console.error('Error fetching stats:', error);
    }
  };

  useEffect(() => {
    fetchStats();
  }, []);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchStats();
    setRefreshing(false);
  };

  const formatCurrency = (value: number) => {
    return 'R$ ' + value.toFixed(2).replace('.', ',');
  };

  return (
    <ScrollView
      style={styles.container}
      refreshControl={
        <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
      }
    >
      {/* Header */}
      <View style={styles.header}>
        <View>
          <Text style={styles.greeting}>Olá, {user?.name}!</Text>
          <Text style={styles.storeName}>{user?.store?.name || 'Minha Loja'}</Text>
        </View>
        <TouchableOpacity onPress={() => router.push('/(admin)/settings')}>
          <Text style={styles.settingsIcon}>⚙️</Text>
        </TouchableOpacity>
      </View>

      {/* Stats Cards */}
      <View style={styles.statsGrid}>
        <View style={[styles.statCard, styles.statCardPrimary]}>
          <Text style={styles.statLabel}>Pedidos Hoje</Text>
          <Text style={styles.statValue}>{stats?.today?.orders || 0}</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statLabel}>Pendentes</Text>
          <Text style={[styles.statValue, { color: '#FF4500' }]}>
            {stats?.pending || 0}
          </Text>
        </View>
      </View>

      <View style={styles.statsGrid}>
        <View style={styles.statCard}>
          <Text style={styles.statLabel}>Esta Semana</Text>
          <Text style={styles.statValue}>{stats?.week?.orders || 0}</Text>
          <Text style={styles.statSubtext}>{formatCurrency(stats?.week?.revenue || 0)}</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statLabel}>Este Mês</Text>
          <Text style={styles.statValue}>{stats?.month?.orders || 0}</Text>
          <Text style={styles.statSubtext}>{formatCurrency(stats?.month?.revenue || 0)}</Text>
        </View>
      </View>

      {/* Menu */}
      <Text style={styles.sectionTitle}>Gerenciamento</Text>

      <View style={styles.menuGrid}>
        <TouchableOpacity 
          style={styles.menuItem}
          onPress={() => router.push('/(admin)/products')}
        >
          <Text style={styles.menuIcon}>📦</Text>
          <Text style={styles.menuLabel}>Produtos</Text>
        </TouchableOpacity>

        <TouchableOpacity 
          style={styles.menuItem}
          onPress={() => router.push('/(admin)/orders')}
        >
          <Text style={styles.menuIcon}>📋</Text>
          <Text style={styles.menuLabel}>Pedidos</Text>
        </TouchableOpacity>

        <TouchableOpacity 
          style={styles.menuItem}
          onPress={() => router.push('/(admin)/settings')}
        >
          <Text style={styles.menuIcon}>🏪</Text>
          <Text style={styles.menuLabel}>Loja</Text>
        </TouchableOpacity>

        <TouchableOpacity 
          style={styles.menuItem}
          onPress={() => router.push('/(admin)/settings')}
        >
          <Text style={styles.menuIcon}>📊</Text>
          <Text style={styles.menuLabel}>Estatísticas</Text>
        </TouchableOpacity>
      </View>

      {/* Quick Actions */}
      <Text style={styles.sectionTitle}>Ações Rápidas</Text>

      <TouchableOpacity 
        style={styles.actionButton}
        onPress={() => router.push('/(admin)/products')}
      >
        <Text style={styles.actionIcon}>➕</Text>
        <View style={styles.actionContent}>
          <Text style={styles.actionTitle}>Novo Produto</Text>
          <Text style={styles.actionSubtitle}>Adicionar produto ao catálogo</Text>
        </View>
      </TouchableOpacity>

      <TouchableOpacity 
        style={styles.actionButton}
        onPress={() => router.push('/(admin)/orders')}
      >
        <Text style={styles.actionIcon}>👁️</Text>
        <View style={styles.actionContent}>
          <Text style={styles.actionTitle}>Ver Pedidos</Text>
          <Text style={styles.actionSubtitle}>
            {stats?.pending ? `${stats.pending} pedidos pendentes` : 'Nenhum pedido pendente'}
          </Text>
        </View>
      </TouchableOpacity>
    </ScrollView>
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
    padding: 20,
    backgroundColor: '#FF4500',
  },
  greeting: {
    color: '#fff',
    fontSize: 16,
  },
  storeName: {
    color: '#fff',
    fontSize: 24,
    fontWeight: 'bold',
  },
  settingsIcon: {
    fontSize: 24,
  },
  statsGrid: {
    flexDirection: 'row',
    paddingHorizontal: 16,
    gap: 12,
    marginTop: 16,
  },
  statCard: {
    flex: 1,
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    alignItems: 'center',
  },
  statCardPrimary: {
    backgroundColor: '#FF4500',
  },
  statLabel: {
    fontSize: 12,
    color: '#666',
    marginBottom: 4,
  },
  statValue: {
    fontSize: 28,
    fontWeight: 'bold',
    color: '#333',
  },
  statSubtext: {
    fontSize: 12,
    color: '#666',
    marginTop: 4,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
    padding: 16,
    paddingBottom: 8,
  },
  menuGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    paddingHorizontal: 16,
    gap: 12,
  },
  menuItem: {
    width: '47%',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 20,
    alignItems: 'center',
  },
  menuIcon: {
    fontSize: 32,
    marginBottom: 8,
  },
  menuLabel: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
  },
  actionButton: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#fff',
    marginHorizontal: 16,
    marginBottom: 12,
    borderRadius: 12,
    padding: 16,
  },
  actionIcon: {
    fontSize: 24,
    marginRight: 16,
  },
  actionContent: {
    flex: 1,
  },
  actionTitle: {
    fontSize: 16,
    fontWeight: '600',
    color: '#333',
  },
  actionSubtitle: {
    fontSize: 12,
    color: '#666',
    marginTop: 2,
  },
});
