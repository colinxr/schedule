import { redirect } from 'next/navigation';
import { checkAuth } from '@/lib/auth';
import DashboardLayout from '../layouts/DashboardLayout';

export default async function Layout({ children }: { children: React.ReactNode }) {
  const user = await checkAuth();

  if (!user) {
    redirect('/login');
  }

  return <DashboardLayout>{children}</DashboardLayout>;
} 