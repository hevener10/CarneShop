import { StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { useLocalSearchParams, useRouter } from 'expo-router';

export default function ProductDetailsScreen() {
  const router = useRouter();
  const params = useLocalSearchParams();
  const id = params.id as string;

  return (
    <View style={styles.container}>
      <TouchableOpacity onPress={() => router.back()}>
        <Text style={styles.backButton}>Voltar</Text>
      </TouchableOpacity>

      <Text style={styles.title}>Produto #{id}</Text>
      <Text style={styles.description}>
        Tela inicial de detalhes criada para estabilizar a navegacao do painel.
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 24,
    backgroundColor: '#f5f5f5',
    gap: 16,
  },
  backButton: {
    color: '#FF4500',
    fontSize: 16,
    fontWeight: '600',
  },
  title: {
    fontSize: 24,
    fontWeight: '700',
    color: '#222',
  },
  description: {
    fontSize: 15,
    lineHeight: 22,
    color: '#555',
  },
});
