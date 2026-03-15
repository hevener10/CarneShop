import { Stack } from 'expo-router';
import { useEffect } from 'react';
import { useAuthStore } from '../src/stores/authStore';
import { Redirect } from 'expo-router';

export default function AdminLayout() {
  const { isAuthenticated, isLoading, user } = useAuthStore();

  if (isLoading) {
    return null;
  }

  if (!isAuthenticated) {
    return <Redirect href="/(auth)/login" />;
  }

  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="index" />
      <Stack.Screen name="products" />
      <Stack.Screen name="orders" />
      <Stack.Screen name="settings" />
    </Stack>
  );
}
