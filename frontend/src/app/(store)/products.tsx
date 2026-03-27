import { useEffect, useState } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, TextInput, Image, RefreshControl } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import api from '@/services/api';
import { Product, Category } from '@/types';

export default function ProductsList() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const categoryId = params.categoryId as string;
  const categoryName = params.categoryName as string;
  const storeSlug = params.store || 'demo';
  
  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');
  const [selectedCategory, setSelectedCategory] = useState<number | null>(categoryId ? parseInt(categoryId) : null);

  const fetchProducts = async () => {
    try {
      const params: any = {};
      if (search) params.search = search;
      if (selectedCategory) params.category_id = selectedCategory;
      
      const response = await api.getClient().get(`/public/stores/${storeSlug}/products`, { params });
      setProducts(response.data.data.data || response.data.data);
    } catch (error) {
      console.error('Error fetching products:', error);
    }
  };

  const fetchCategories = async () => {
    try {
      const response = await api.getClient().get(`/public/stores/${storeSlug}/categories`);
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

  const formatPrice = (price: number) => {
    return 'R$ ' + price.toFixed(2).replace('.', ',');
  };

  const renderProduct = ({ item }: { item: Product }) => (
    <TouchableOpacity 
      style={styles.productCard}
      onPress={() => router.push({ pathname: '/(store)/product/[slug]', params: { slug: item.slug, store: storeSlug } })}
    >
      <View style={styles.productImage}>
        {item.image ? (
          <Image source={{ uri: item.image }} style={styles.productImg} />
        ) : (
          <View style={styles.productPlaceholder}><Text>🥩</Text></View>
        )}
      </View>
      <View style={styles.productInfo}>
        <Text style={styles.productName} numberOfLines={2}>{item.name}</Text>
        <Text style={styles.productCategory}>{item.category?.name}</Text>
        <View style={styles.productPrices}>
          <Text style={styles.productPrice}>{formatPrice(item.discount_price || item.price)}/kg</Text>
          {item.discount_price && (
            <Text style={styles.productOldPrice}>{formatPrice(item.price)}/kg</Text>
          )}
        </View>
      </View>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()}>
          <Text style={styles.backButton}>←</Text>
        </TouchableOpacity>
        <Text style={styles.title}>{categoryName || 'Produtos'}</Text>
        <TouchableOpacity onPress={() => router.push({ pathname: '/(store)/cart', params: { store: storeSlug } })}>
          <Text style={styles.cartButton}>🛒</Text>
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
            onPress={() => setSelectedCategory(item.id as any)}
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

      {/* Products Grid */}
      <FlatList
        data={products}
        keyExtractor={(item) => String(item.id)}
        renderItem={renderProduct}
        numColumns={2}
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
  cartButton: {
    fontSize: 24,
  },
  searchContainer: {
    padding: 16,
    paddingBottom: 8,
  },
  searchInput: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 12,
    fontSize: 16,
  },
  categoriesList: {
    paddingHorizontal: 16,
    paddingBottom: 16,
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
    padding: 8,
  },
  productCard: {
    flex: 1,
    backgroundColor: '#fff',
    borderRadius: 12,
    margin: 8,
    overflow: 'hidden',
    maxWidth: '47%',
  },
  productImage: {
    height: 120,
    backgroundColor: '#f5f5f5',
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
  productInfo: {
    padding: 12,
  },
  productName: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
    height: 36,
  },
  productCategory: {
    fontSize: 11,
    color: '#999',
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
    fontSize: 11,
    color: '#999',
    textDecorationLine: 'line-through',
    marginLeft: 4,
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
