import { redirect } from 'next/navigation';
import { checkAuth } from '@/lib/auth';
import DashboardLayout from '@/app/layouts/DashboardLayout';

export default async function Layout({ children }: { children: React.ReactNode }) {
  return <DashboardLayout >{children}</DashboardLayout>;
} 