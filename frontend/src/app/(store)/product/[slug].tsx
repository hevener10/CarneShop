import { useEffect, useState } from 'react';
import { Alert, Image, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';
import api from '@/services/api';
import { useCartStore } from '@/stores/cartStore';
import { Product, ProductVariation } from '@/types';

export default function ProductDetailScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const slug = params.slug as string;
  const storeSlug = (params.store as string) || 'demo';

  const { addItem, setStore } = useCartStore();
  const [product, setProduct] = useState<Product | null>(null);
  const [selectedVariation, setSelectedVariation] = useState<ProductVariation | undefined>();
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const loadProduct = async () => {
      try {
        const response = await api.getClient().get(`/public/stores/${storeSlug}/products/${slug}`);
        const fetchedProduct = response.data.data as Product;
        setProduct(fetchedProduct);
        setSelectedVariation(fetchedProduct.variations?.[0]);
      } catch (error) {
        console.error('Error fetching product:', error);
        Alert.alert('Erro', 'Nao foi possivel carregar o produto');
        router.back();
      } finally {
        setLoading(false);
      }
    };

    loadProduct();
  }, [router, slug, storeSlug]);

  const formatPrice = (price: number) => {
    return 'R$ ' + price.toFixed(2).replace('.', ',');
  };

  const handleAddToCart = () => {
    if (!product) {
      return;
    }

    setStore(product.store_id, storeSlug);
    addItem(product, selectedVariation);
    Alert.alert('Carrinho', 'Produto adicionado ao carrinho');
  };

  if (loading || !product) {
    return (
      <View style={styles.centered}>
        <Text style={styles.loadingText}>Carregando produto...</Text>
      </View>
    );
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <TouchableOpacity onPress={() => router.back()}>
        <Text style={styles.backButton}>Voltar</Text>
      </TouchableOpacity>

      <View style={styles.imageContainer}>
        {product.image ? (
          <Image source={{ uri: product.image }} style={styles.image} />
        ) : (
          <View style={styles.placeholder}>
            <Text style={styles.placeholderText}>Produto</Text>
          </View>
        )}
      </View>

      <Text style={styles.name}>{product.name}</Text>
      <Text style={styles.price}>{formatPrice(product.discount_price || product.price)}/kg</Text>

      {product.description ? (
        <Text style={styles.description}>{product.description}</Text>
      ) : null}

      {product.variations && product.variations.length > 0 ? (
        <View style={styles.section}>
          <Text style={styles.sectionTitle}>Corte</Text>
          <View style={styles.variations}>
            {product.variations.map((variation) => (
              <TouchableOpacity
                key={variation.id}
                style={[
                  styles.variationChip,
                  selectedVariation?.id === variation.id && styles.variationChipActive,
                ]}
                onPress={() => setSelectedVariation(variation)}
              >
                <Text
                  style={[
                    styles.variationText,
                    selectedVariation?.id === variation.id && styles.variationTextActive,
                  ]}
                >
                  {variation.name}
                </Text>
              </TouchableOpacity>
            ))}
          </View>
        </View>
      ) : null}

      <TouchableOpacity style={styles.button} onPress={handleAddToCart}>
        <Text style={styles.buttonText}>Adicionar ao carrinho</Text>
      </TouchableOpacity>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#f5f5f5',
  },
  content: {
    padding: 20,
    gap: 16,
  },
  centered: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    backgroundColor: '#f5f5f5',
  },
  loadingText: {
    fontSize: 16,
    color: '#666',
  },
  backButton: {
    fontSize: 16,
    color: '#FF4500',
    fontWeight: '600',
  },
  imageContainer: {
    borderRadius: 20,
    overflow: 'hidden',
    backgroundColor: '#fff',
  },
  image: {
    width: '100%',
    height: 260,
  },
  placeholder: {
    height: 260,
    alignItems: 'center',
    justifyContent: 'center',
  },
  placeholderText: {
    fontSize: 24,
    color: '#999',
  },
  name: {
    fontSize: 28,
    fontWeight: '700',
    color: '#222',
  },
  price: {
    fontSize: 22,
    fontWeight: '700',
    color: '#FF4500',
  },
  description: {
    fontSize: 15,
    lineHeight: 22,
    color: '#555',
  },
  section: {
    backgroundColor: '#fff',
    borderRadius: 16,
    padding: 16,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: '#222',
    marginBottom: 12,
  },
  variations: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 8,
  },
  variationChip: {
    borderRadius: 999,
    borderWidth: 1,
    borderColor: '#ddd',
    paddingHorizontal: 14,
    paddingVertical: 10,
  },
  variationChipActive: {
    borderColor: '#FF4500',
    backgroundColor: '#FFF1EA',
  },
  variationText: {
    color: '#555',
    fontWeight: '600',
  },
  variationTextActive: {
    color: '#FF4500',
  },
  button: {
    backgroundColor: '#FF4500',
    borderRadius: 14,
    paddingVertical: 16,
    alignItems: 'center',
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '700',
  },
});
