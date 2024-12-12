import { ReactNode } from 'react';
import Sidebar from '../components/dashboard/Sidebar';
import Header from '../components/dashboard/Header';

interface DashboardLayoutProps {
  children: ReactNode;
}

export default function DashboardLayout({ children }: DashboardLayoutProps) {
  return (
    <div className="min-h-screen bg-background flex w-full">
      <Sidebar />
      <div className="flex-1">
        <Header className="sticky top-0 z-50" />
        <main className="container mx-auto p-6">
          {children}
        </main>
      </div>
    </div>
  );
} 