import { useEffect, useState } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, TextInput, Image, ScrollView, RefreshControl, Linking } from 'react-native';
import { useRouter, useLocalSearchParams } from 'expo-router';
import api from '@/services/api';
import { useCartStore } from '@/stores/cartStore';
import { Store, Category, Product, Banner } from '@/types';

export default function StoreHome() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const slug = params.slug as string || 'demo';
  
  const [store, setStore] = useState<Store | null>(null);
  const [categories, setCategories] = useState<Category[]>([]);
  const [products, setProducts] = useState<Product[]>([]);
  const [banners, setBanners] = useState<Banner[]>([]);
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [search, setSearch] = useState('');
  
  const { itemCount } = useCartStore();

  const fetchData = async () => {
    try {
      const [storeRes, categoriesRes, productsRes, bannersRes] = await Promise.all([
        api.getClient().get(`/public/stores/${slug}`),
        api.getClient().get(`/public/stores/${slug}/categories`),
        api.getClient().get(`/public/stores/${slug}/products?featured=true`),
        api.getClient().get(`/public/stores/${slug}/banners`),
      ]);
      
      setStore(storeRes.data.data);
      setCategories(categoriesRes.data.data);
      setProducts(productsRes.data.data.data || productsRes.data.data);
      setBanners(bannersRes.data.data);
    } catch (error) {
      console.error('Error fetching store:', error);
    }
  };

  useEffect(() => {
    const load = async () => {
      setLoading(true);
      await fetchData();
      setLoading(false);
    };
    load();
  }, [slug]);

  const onRefresh = async () => {
    setRefreshing(true);
    await fetchData();
    setRefreshing(false);
  };

  const formatPrice = (price: number) => {
    return 'R$ ' + price.toFixed(2).replace('.', ',');
  };

  const handleWhatsApp = () => {
    if (store?.whatsapp) {
      Linking.openURL(store.whatsapp);
    }
  };

  const renderProduct = ({ item }: { item: Product }) => (
    <TouchableOpacity 
      style={styles.productCard}
      onPress={() => router.push({ pathname: '/(store)/product/[slug]', params: { slug: item.slug } })}
    >
      <View style={styles.productImage}>
        {item.image ? (
          <Image source={{ uri: item.image }} style={styles.productImg} />
        ) : (
          <View style={styles.productPlaceholder}><Text>🥩</Text></View>
        )}
        {item.discount_price && (
          <View style={styles.discountBadge}>
            <Text style={styles.discountText}>-{item.discount_percent || Math.round(((item.price - item.discount_price) / item.price) * 100)}%</Text>
          </View>
        )}
      </View>
      <View style={styles.productInfo}>
        <Text style={styles.productName} numberOfLines={2}>{item.name}</Text>
        <View style={styles.productPrices}>
          <Text style={styles.productPrice}>{formatPrice(item.discount_price || item.price)}/kg</Text>
          {item.discount_price && (
            <Text style={styles.productOldPrice}>{formatPrice(item.price)}/kg</Text>
          )}
        </View>
        <TouchableOpacity style={styles.addButton}>
          <Text style={styles.addButtonText}>Adicionar</Text>
        </TouchableOpacity>
      </View>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <View style={styles.headerContent}>
          {store?.logo ? (
            <Image source={{ uri: store.logo }} style={styles.logo} />
          ) : (
            <Text style={styles.logoText}>🦔</Text>
          )}
          <View style={styles.headerInfo}>
            <Text style={styles.storeName}>{store?.name || 'CarneShop'}</Text>
            <Text style={styles.storeInfo}>
              {store?.city && store?.state ? `${store.city}, ${store.state}` : 'Delivery de Carnes'}
            </Text>
          </View>
        </View>
        <TouchableOpacity onPress={() => router.push('/(store)/cart')}>
          <View style={styles.cartButton}>
            <Text style={styles.cartIcon}>🛒</Text>
            {itemCount > 0 && (
              <View style={styles.cartBadge}>
                <Text style={styles.cartBadgeText}>{itemCount}</Text>
              </View>
            )}
          </View>
        </TouchableOpacity>
      </View>

      <ScrollView 
        refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
        showsVerticalScrollIndicator={false}
      >
        {/* Search */}
        <View style={styles.searchContainer}>
          <TextInput
            style={styles.searchInput}
            placeholder="Buscar produtos..."
            value={search}
            onChangeText={setSearch}
          />
        </View>

        {/* Banners */}
        {banners.length > 0 && (
          <FlatList
            horizontal
            data={banners}
            keyExtractor={(item) => String(item.id)}
            renderItem={({ item }) => (
              <TouchableOpacity style={styles.banner}>
                <Image source={{ uri: item.image }} style={styles.bannerImage} />
              </TouchableOpacity>
            )}
            showsHorizontalScrollIndicator={false}
            style={styles.bannersList}
            contentContainerStyle={styles.bannersContent}
          />
        )}

        {/* Categories */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Categorias</Text>
          <FlatList
            horizontal
            data={categories}
            keyExtractor={(item) => String(item.id)}
            renderItem={({ item }) => (
              <TouchableOpacity 
                style={styles.categoryChip}
                onPress={() => router.push({ pathname: '/(store)/products', params: { categoryId: item.id, categoryName: item.name } })}
              >
                <Text style={styles.categoryText}>{item.name}</Text>
              </TouchableOpacity>
            )}
            showsHorizontalScrollIndicator={false}
          />
        </View>

        {/* Featured Products */}
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Destaques</Text>
          <FlatList
            horizontal
            data={products}
            keyExtractor={(item) => String(item.id)}
            renderItem={renderProduct}
            showsHorizontalScrollIndicator={false}
          />
        </View>

        {/* All Products */}
        <View style={styles.section}>
          <View style={styles.sectionHeader}>
            <Text style={styles.sectionTitle}>Todos os Produtos</Text>
            <TouchableOpacity onPress={() => router.push('/(store)/products')}>
              <Text style={styles.seeAll}>Ver todos →</Text>
            </TouchableOpacity>
          </View>
          <FlatList
            data={products.slice(0, 4)}
            keyExtractor={(item) => String(item.id)}
            renderItem={renderProduct}
            scrollEnabled={false}
          />
        </View>

        <View style={{ height: 100 }} />
      </ScrollView>

      {/* WhatsApp Button */}
      {store?.whatsapp && (
        <TouchableOpacity style={styles.whatsappButton} onPress={handleWhatsApp}>
          <Text style={styles.whatsappIcon}>💬</Text>
          <Text style={styles.whatsappText}>Falar no WhatsApp</Text>
        </TouchableOpacity>
      )}
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
  headerContent: {
    flexDirection: 'row',
    alignItems: 'center',
    flex: 1,
  },
  logo: {
    width: 40,
    height: 40,
    borderRadius: 20,
    marginRight: 12,
  },
  logoText: {
    fontSize: 32,
    marginRight: 12,
  },
  headerInfo: {
    flex: 1,
  },
  storeName: {
    color: '#fff',
    fontSize: 18,
    fontWeight: 'bold',
  },
  storeInfo: {
    color: 'rgba(255,255,255,0.8)',
    fontSize: 12,
  },
  cartButton: {
    position: 'relative',
    padding: 8,
  },
  cartIcon: {
    fontSize: 24,
  },
  cartBadge: {
    position: 'absolute',
    top: 0,
    right: 0,
    backgroundColor: '#fff',
    borderRadius: 10,
    width: 20,
    height: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  cartBadgeText: {
    color: '#FF4500',
    fontSize: 12,
    fontWeight: 'bold',
  },
  searchContainer: {
    padding: 16,
  },
  searchInput: {
    backgroundColor: '#fff',
    borderRadius: 12,
    padding: 12,
    fontSize: 16,
  },
  bannersList: {
    marginBottom: 16,
  },
  bannersContent: {
    paddingHorizontal: 16,
  },
  banner: {
    marginRight: 12,
    borderRadius: 12,
    overflow: 'hidden',
  },
  bannerImage: {
    width: 280,
    height: 140,
    borderRadius: 12,
  },
  section: {
    marginBottom: 24,
  },
  sectionHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    paddingHorizontal: 16,
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: 'bold',
    color: '#333',
    paddingHorizontal: 16,
    marginBottom: 12,
  },
  seeAll: {
    color: '#FF4500',
    fontSize: 14,
    fontWeight: '600',
  },
  categoryChip: {
    backgroundColor: '#fff',
    paddingHorizontal: 16,
    paddingVertical: 10,
    borderRadius: 20,
    marginLeft: 16,
  },
  categoryText: {
    fontSize: 14,
    fontWeight: '600',
    color: '#333',
  },
  productCard: {
    width: 160,
    backgroundColor: '#fff',
    borderRadius: 12,
    marginLeft: 16,
    overflow: 'hidden',
  },
  productImage: {
    height: 120,
    backgroundColor: '#f5f5f5',
    position: 'relative',
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
  discountBadge: {
    position: 'absolute',
    top: 8,
    left: 8,
    backgroundColor: '#FF4500',
    paddingHorizontal: 8,
    paddingVertical: 4,
    borderRadius: 8,
  },
  discountText: {
    color: '#fff',
    fontSize: 10,
    fontWeight: 'bold',
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
    marginLeft: 4,
  },
  addButton: {
    backgroundColor: '#FF4500',
    paddingVertical: 8,
    borderRadius: 8,
    alignItems: 'center',
    marginTop: 8,
  },
  addButtonText: {
    color: '#fff',
    fontSize: 12,
    fontWeight: '600',
  },
  whatsappButton: {
    position: 'absolute',
    bottom: 24,
    right: 24,
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: '#25D366',
    paddingHorizontal: 20,
    paddingVertical: 12,
    borderRadius: 24,
    shadowColor: '#000',
    shadowOffset: { width: 0, height: 2 },
    shadowOpacity: 0.2,
    shadowRadius: 4,
    elevation: 4,
  },
  whatsappIcon: {
    fontSize: 20,
    marginRight: 8,
  },
  whatsappText: {
    color: '#fff',
    fontSize: 14,
    fontWeight: 'bold',
  },
});
