import { Redirect } from 'expo-router';
import React from 'react';

export default function Index() {
  // Redirect to scanner screen on app launch
  return <Redirect href="/scanner" />;
}
