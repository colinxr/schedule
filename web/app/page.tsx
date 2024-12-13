'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import { Loader2 } from 'lucide-react';

export default function HomePage() {
  const router = useRouter();
  const { isAuthenticated, loading } = useAuth();

  useEffect(() => {
    if (!loading && isAuthenticated) {
      router.replace('/a/conversations');
    }
  }, [isAuthenticated, loading, router]);

  return (
    <div className="flex items-center justify-center h-screen">
      <Loader2 className="h-4 w-4 animate-spin" />
    </div>
  );
}
