import { useEffect, useState } from 'react';
import { View, Text, StyleSheet, TextInput, TouchableOpacity, ScrollView, Alert } from 'react-native';
import { useRouter } from 'expo-router';
import { useAuthStore } from '../src/stores/authStore';
import api from '../src/services/api';

export default function SettingsScreen() {
  const router = useRouter();
  const { user, logout } = useAuthStore();
  
  const [store, setStore] = useState({
    name: '',
    description: '',
    whatsapp: '',
    address: '',
    city: '',
    state: '',
    minimum_order: 50,
    delivery_fee: 0,
  });
  
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    if (user?.store) {
      setStore({
        name: user.store.name || '',
        description: user.store.description || '',
        whatsapp: user.store.whatsapp || '',
        address: user.store.address || '',
        city: user.store.city || '',
        state: user.store.state || '',
        minimum_order: user.store.minimum_order || 50,
        delivery_fee: user.store.delivery_fee || 0,
      });
    }
  }, [user]);

  const handleSave = async () => {
    setLoading(true);
    try {
      await api.getClient().put('/stores/me', store);
      Alert.alert('Sucesso', 'Loja atualizada com sucesso!');
    } catch (error) {
      Alert.alert('Erro', 'Não foi possível salvar as alterações');
    } finally {
      setLoading(false);
    }
  };

  const handleLogout = () => {
    Alert.alert(
      'Sair',
      'Tem certeza que deseja sair?',
      [
        { text: 'Cancelar', style: 'cancel' },
        { text: 'Sair', onPress: () => logout().then(() => router.replace('/(auth)/login')) },
      ]
    );
  };

  return (
    <ScrollView style={styles.container}>
      {/* Header */}
      <View style={styles.header}>
        <TouchableOpacity onPress={() => router.back()}>
          <Text style={styles.backButton}>← Voltar</Text>
        </TouchableOpacity>
        <Text style={styles.title}>Configurações</Text>
        <View style={{ width: 40 }} />
      </View>

      {/* Store Info */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Informações da Loja</Text>

        <Text style={styles.label}>Nome da Loja</Text>
        <TextInput
          style={styles.input}
          value={store.name}
          onChangeText={(text) => setStore({ ...store, name: text })}
          placeholder="Nome da sua loja"
        />

        <Text style={styles.label}>Descrição</Text>
        <TextInput
          style={[styles.input, styles.textArea]}
          value={store.description}
          onChangeText={(text) => setStore({ ...store, description: text })}
          placeholder="Descrição da loja"
          multiline
          numberOfLines={3}
        />

        <Text style={styles.label}>WhatsApp</Text>
        <TextInput
          style={styles.input}
          value={store.whatsapp}
          onChangeText={(text) => setStore({ ...store, whatsapp: text })}
          placeholder="(00) 00000-0000"
          keyboardType="phone-pad"
        />
      </View>

      {/* Address */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Endereço</Text>

        <Text style={styles.label}>Rua/Número</Text>
        <TextInput
          style={styles.input}
          value={store.address}
          onChangeText={(text) => setStore({ ...store, address: text })}
          placeholder="Rua, número"
        />

        <View style={styles.row}>
          <View style={styles.halfInput}>
            <Text style={styles.label}>Cidade</Text>
            <TextInput
              style={styles.input}
              value={store.city}
              onChangeText={(text) => setStore({ ...store, city: text })}
              placeholder="Cidade"
            />
          </View>
          <View style={styles.halfInput}>
            <Text style={styles.label}>Estado</Text>
            <TextInput
              style={styles.input}
              value={store.state}
              onChangeText={(text) => setStore({ ...store, state: text })}
              placeholder="UF"
              maxLength={2}
              autoCapitalize="characters"
            />
          </View>
        </View>
      </View>

      {/* Delivery */}
      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Entrega</Text>

        <Text style={styles.label}>Pedido Mínimo (R$)</Text>
        <TextInput
          style={styles.input}
          value={String(store.minimum_order)}
          onChangeText={(text) => setStore({ ...store, minimum_order: parseFloat(text) || 0 })}
          placeholder="50.00"
          keyboardType="decimal-pad"
        />

        <Text style={styles.label}>Taxa de Entrega (R$)</Text>
        <TextInput
          style={styles.input}
          value={String(store.delivery_fee)}
          onChangeText={(text) => setStore({ ...store, delivery_fee: parseFloat(text) || 0 })}
          placeholder="0.00"
          keyboardType="decimal-pad"
        />
      </View>

      {/* Save Button */}
      <TouchableOpacity
        style={[styles.saveButton, loading && styles.saveButtonDisabled]}
        onPress={handleSave}
        disabled={loading}
      >
        <Text style={styles.saveButtonText}>
          {loading ? 'Salvando...' : 'Salvar Alterações'}
        </Text>
      </TouchableOpacity>

      {/* Logout */}
      <TouchableOpacity
        style={styles.logoutButton}
        onPress={handleLogout}
      >
        <Text style={styles.logoutButtonText}>Sair da Conta</Text>
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
  section: {
    backgroundColor: '#fff',
    margin: 16,
    marginBottom: 0,
    borderRadius: 12,
    padding: 16,
  },
  sectionTitle: {
    fontSize: 16,
    fontWeight: 'bold',
    color: '#333',
    marginBottom: 16,
  },
  label: {
    fontSize: 14,
    fontWeight: '600',
    color: '#666',
    marginBottom: 8,
  },
  input: {
    backgroundColor: '#f5f5f5',
    borderRadius: 8,
    padding: 12,
    fontSize: 16,
    marginBottom: 16,
    borderWidth: 1,
    borderColor: '#e0e0e0',
  },
  textArea: {
    height: 80,
    textAlignVertical: 'top',
  },
  row: {
    flexDirection: 'row',
    gap: 12,
  },
  halfInput: {
    flex: 1,
  },
  saveButton: {
    backgroundColor: '#FF4500',
    margin: 16,
    padding: 16,
    borderRadius: 12,
    alignItems: 'center',
  },
  saveButtonDisabled: {
    opacity: 0.6,
  },
  saveButtonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: 'bold',
  },
  logoutButton: {
    margin: 16,
    marginTop: 0,
    padding: 16,
    borderRadius: 12,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: '#F44336',
  },
  logoutButtonText: {
    color: '#F44336',
    fontSize: 16,
    fontWeight: '600',
  },
});
