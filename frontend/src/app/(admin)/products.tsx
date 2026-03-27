import { useEffect, useState } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, TextInput, RefreshControl, Alert } from 'react-native';
import { useRouter } from 'expo-router';
import api from '@/services/api';
import { Product, Category } from '@/types';

export default function ProductsScreen() {
  const router = useRouter();
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');
  const [selectedCategory, setSelectedCategory] = useState<number | null>(null);

  const fetchProducts = async () => {
    try {
      const params: any = {};
      if (search) params.search = search;
      if (selectedCategory) params.category_id = selectedCategory;
      
      const response = await api.getClient().get('/stores/me/products', { params });
      setProducts(response.data.data.data || response.data.data);
    } catch (error) {
      console.error('Error fetching products:', error);
    }
  };

  const fetchCategories = async () => {
    try {
      const response = await api.getClient().get('/stores/me/categories');
      setCategories(response.data.data);
    } catch (error) {
      console.error('Error fetching categories:', error);
    }
  };

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      await Promise.all([fetchProducts(), fetchCategories()]);
      setLoading(false);
    };
    load();
  }, []);

  useEffect(() => {
    const debounce = setTimeout(() => {
      fetchProducts();
    }, 500);

    return () => clearTimeout(debounce);
  }, [search, selectedCategory]);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchProducts();
    setRefreshing(false);
  };

  const toggleProduct = async (product: Product) => {
    try {
      await api.getClient().put(`/stores/me/products/${product.id}/toggle`);
      fetchProducts();
    } catch (error) {
      Alert.alert('Erro', 'Não foi possível atualizar o produto');
    }
  };

  const formatPrice = (price: number) => {
    return 'R$ ' + price.toFixed(2).replace('.', ',');
  };

  const renderProduct = ({ item }: { item: Product }) => (
    <TouchableOpacity 
      style={[styles.productCard, !item.is_active && styles.productCardInactive]}
      onPress={() => router.push(`/(admin)/products/${item.id}` as never)}
    >
      <View style={styles.productInfo}>
        <Text style={styles.productName}>{item.name}</Text>
        <Text style={styles.productCategory}>{item.category?.name || 'Sem categoria'}</Text>
        <View style={styles.productPrices}>
          <Text style={styles.productPrice}>{formatPrice(item.discount_price || item.price)}/kg</Text>
          {item.discount_price && (
            <Text style={styles.productOldPrice}>{formatPrice(item.price)}/kg</Text>
          )}
        </View>
      </View>
      <TouchableOpacity 
        style={[styles.toggleButton, item.is_active ? styles.toggleActive : styles.toggleInactive]}
        onPress={() => toggleProduct(item)}
      >
        <Text style={styles.toggleText}>{item.is_active ? 'Ativo' : 'Inativo'}</Text>
      </TouchableOpacity>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()}>
          <Text style={styles.backButton}>← Voltar</Text>
        </TouchableOpacity>
        <Text style={styles.title}>Produtos</Text>
        <TouchableOpacity onPress={() => router.push('/(admin)/products/new' as never)}>
          <Text style={styles.addButton}>➕</Text>
        </TouchableOpacity>
      </View>

      {/* Search */}
      <View style={styles.searchContainer}>
        <TextInput
          style={styles.searchInput}
          placeholder="Buscar produtos..."
          value={search}
          onChangeText={setSearch}
        />
      </View>

      {/* Categories Filter */}
      <FlatList
        horizontal
        data={[{ id: null, name: 'Todos' }, ...categories]}
        keyExtractor={(item) => String(item.id)}
        renderItem={({ item }) => (
          <TouchableOpacity
            style={[
              styles.categoryChip,
              selectedCategory === item.id && styles.categoryChipActive
            ]}
            onPress={() => setSelectedCategory(item.id)}
          >
            <Text style={[
              styles.categoryChipText,
              selectedCategory === item.id && styles.categoryChipTextActive
            ]}>
              {item.name}
            </Text>
          </TouchableOpacity>
        )}
        style={styles.categoriesList}
        showsHorizontalScrollIndicator={false}
      />

      {/* Products List */}
      <FlatList
        data={products}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderProduct}
        refreshControl={
          <RefreshControl refreshing={refreshing} onRefresh={onRefresh} />
        }
        contentContainerStyle={styles.list}
        ListEmptyComponent={
          <View style={styles.empty}>
            <Text style={styles.emptyText}>Nenhum produto encontrado</Text>
          </View>
        }
      />
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
  addButton: {
    fontSize: 24,
  },
  searchContainer: {
    padding: 16,
    paddingTop: 8,
  },
  searchInput: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 12,
    fontSize: 16,
  },
  categoriesList: {
    paddingHorizontal: 16,
    marginBottom: 8,
  },
  categoryChip: {
    paddingHorizontal: 16,
    paddingVertical: 8,
    borderRadius: 20,
    backgroundColor: '#fff',
    marginRight: 8,
  },
  categoryChipActive: {
    backgroundColor: '#FF4500',
  },
  categoryChipText: {
    fontSize: 14,
    color: '#666',
  },
  categoryChipTextActive: {
    color: '#fff',
    fontWeight: '600',
  },
  list: {
    padding: 16,
  },
  productCard: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 16,
    marginBottom: 12,
  },
  productCardInactive: {
    opacity: 0.6,
  },
  productInfo: {
    flex: 1,
  },
  productName: {
    fontSize: 16,
    fontWeight: '600',
    color: '#333',
  },
  productCategory: {
    fontSize: 12,
    color: '#666',
    marginTop: 2,
  },
  productPrices: {
    flexDirection: 'row',
    alignItems: 'center',
    marginTop: 4,
  },
  productPrice: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#FF4500',
  },
  productOldPrice: {
    fontSize: 12,
    color: '#999',
    textDecorationLine: 'line-through',
    marginLeft: 8,
  },
  toggleButton: {
    paddingHorizontal: 12,
    paddingVertical: 6,
    borderRadius: 16,
  },
  toggleActive: {
    backgroundColor: '#4CAF50',
  },
  toggleInactive: {
    backgroundColor: '#ccc',
  },
  toggleText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '600',
  },
  empty: {
    alignItems: 'center',
    padding: 40,
  },
  emptyText: {
    fontSize: 16,
    color: '#666',
  },
});
