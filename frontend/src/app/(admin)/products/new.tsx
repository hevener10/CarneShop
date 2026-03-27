import { StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { useRouter } from 'expo-router';

/**
 * Renderiza a tela placeholder para criacao de produto no painel administrativo.
 */
export default function NewProductScreen() {
  const router = useRouter();

  return (
    <View style={styles.container}>
      <TouchableOpacity onPress={() => router.back()}>
        <Text style={styles.backButton}>Voltar</Text>
      </TouchableOpacity>

      <Text style={styles.title}>Novo produto</Text>
      <Text style={styles.description}>
        Tela base criada para a rota existir no painel. O proximo passo natural e ligar este fluxo ao endpoint de cadastro.
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
