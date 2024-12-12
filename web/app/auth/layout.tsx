'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/contexts/AuthContext';
import { Loader2 } from 'lucide-react';

export default function AuthLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();
  const { isAuthenticated, loading } = useAuth();

  useEffect(() => {
    console.log('isAuthenticated', isAuthenticated);
    console.log('loading', loading);
    
    // if (!loading && isAuthenticated) {
    //   router.push('/a/conversations');
    // }
  }, [isAuthenticated, loading, router]);

//   if (loading) {
//     return (
//       <div className="flex items-center justify-center h-screen">
//         <Loader2 className="h-4 w-4 animate-spin" />
//       </div>
//     );
//   }

//   if (isAuthenticated) {
//     return null;
//   }

  return children;
} 