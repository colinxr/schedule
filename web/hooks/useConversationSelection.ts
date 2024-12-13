import { create } from 'zustand';
import { OpenConversationStore } from '@/stores/openConversationStore';

export const useOpenConversations = create<OpenConversationStore>((set) => ({
  isOpen: false,
  setOpen: (open) => set({ isOpen: open }),
})); 