import { Stack } from 'expo-router';

export default function StoreLayout() {
  return (
    <Stack screenOptions={{ headerShown: false }}>
      <Stack.Screen name="index" />
      <Stack.Screen name="products" />
      <Stack.Screen name="product/[slug]" />
      <Stack.Screen name="cart" />
      <Stack.Screen name="checkout" />
    </Stack>
  );
}
